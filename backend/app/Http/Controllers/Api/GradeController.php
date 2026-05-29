<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use App\Models\Grade;
use App\Models\GradeAuditLog;
use App\Models\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GradeController — Gestión de notas académicas (core del sistema).
 *
 * Reglas de acceso:
 *   - admin:   CRUD completo en cualquier nota
 *   - teacher: crear/editar/eliminar notas de sus cursos asignados
 *              (solo en períodos abiertos para editar/eliminar)
 *   - student: solo lectura de sus propias notas
 *
 * Cada mutación registra una entrada en grade_audit_log.
 *
 * Endpoints:
 *   GET    /api/grades                          → Listar notas (filtrado por rol)
 *   POST   /api/grades                          → Crear nota
 *   GET    /api/grades/{id}                     → Ver nota
 *   PUT    /api/grades/{id}                     → Actualizar nota
 *   DELETE /api/grades/{id}                     → Eliminar nota
 *   GET    /api/grades/student/{studentId}      → Notas de un estudiante
 *   GET    /api/grades/report/{courseId}/{pid}  → Reporte por curso y período
 */
class GradeController extends Controller
{
    /**
     * Lista notas con filtros según el rol del usuario.
     *
     * Query params opcionales:
     *   ?student_id, ?course_id, ?subject_id, ?period_id, ?competency
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        
        // Validar que estudiantes y docentes tengan perfil completo
        if ($user->isStudent() && !$user->student) {
            return response()->json([
                'message' => 'Tu perfil de estudiante no está completado.',
            ], 422);
        }
        
        if ($user->isTeacher() && !$user->teacher) {
            return response()->json([
                'message' => 'Tu perfil de docente no está completado.',
            ], 422);
        }
        
        $query = Grade::with(['student.user', 'course', 'subject', 'period']);

        // Restricción por rol
        if ($user->isStudent() && $user->student) {
            $query->where('student_id', $user->student->id);
        } elseif ($user->isTeacher() && $user->teacher) {
            // Solo las notas de los cursos+asignaturas del docente
            $teacherId   = $user->teacher->id;
            $assignments = $user->teacher->assignments()->get(['course_id', 'subject_id']);

            $query->where(function ($q) use ($assignments) {
                foreach ($assignments as $a) {
                    $q->orWhere(function ($inner) use ($a) {
                        $inner->where('course_id', $a->course_id)
                              ->where('subject_id', $a->subject_id);
                    });
                }
            });
        }

        // Filtros adicionales aplicados sobre el scope de rol
        $query->when($request->student_id,  fn($q) => $q->where('student_id',  $request->student_id))
              ->when($request->course_id,   fn($q) => $q->where('course_id',   $request->course_id))
              ->when($request->subject_id,  fn($q) => $q->where('subject_id',  $request->subject_id))
              ->when($request->period_id,   fn($q) => $q->where('period_id',   $request->period_id))
              ->when($request->competency,  fn($q) => $q->where('competency',  $request->competency));

        $grades = $query->orderBy('period_id')->orderBy('subject_id')->paginate(50);

        return response()->json($grades, 200);
    }

    /**
     * Crea una nueva nota o actualiza si ya existe (upsert).
     *
     * Validaciones adicionales de negocio:
     *  - El período debe estar abierto
     *  - El docente debe tener asignación en el curso+asignatura
     *  - El estudiante debe pertenecer al curso
     */
    public function store(StoreGradeRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verificar que el período esté abierto
        $period = Period::findOrFail($request->period_id);
        if (! $period->is_open && ! $user->isAdmin()) {
            return response()->json([
                'message' => 'El período está cerrado. No se pueden registrar notas.',
                'error'   => 'PERIOD_CLOSED',
            ], 422);
        }

        // Docente: verificar que tenga asignación para este curso y asignatura
        if ($user->isTeacher()) {
            $hasAssignment = $user->teacher->assignments()
                ->where('course_id', $request->course_id)
                ->where('subject_id', $request->subject_id)
                ->exists();

            if (! $hasAssignment) {
                return response()->json([
                    'message' => 'No tienes asignación para dictar esta asignatura en este curso.',
                    'error'   => 'ASSIGNMENT_NOT_FOUND',
                ], 403);
            }
        }

        // Verificar que el estudiante pertenezca al curso
        $grade = Grade::where([
            'student_id' => $request->student_id,
            'course_id'  => $request->course_id,
            'subject_id' => $request->subject_id,
            'period_id'  => $request->period_id,
            'competency' => $request->competency,
        ])->first();

        DB::beginTransaction();

        try {
            $previousGrade = $grade?->grade;
            $action        = $grade ? 'updated' : 'created';

            // Crear o actualizar la nota
            $grade = Grade::updateOrCreate(
                [
                    'student_id' => $request->student_id,
                    'course_id'  => $request->course_id,
                    'subject_id' => $request->subject_id,
                    'period_id'  => $request->period_id,
                    'competency' => $request->competency,
                ],
                ['grade' => $request->grade]
            );

            // Registrar en bitácora
            $this->logAudit($user, $grade, $action, $previousGrade, $grade->grade);

            DB::commit();

            $grade->load(['student.user', 'course', 'subject', 'period']);

            return response()->json([
                'message' => $action === 'created'
                    ? 'Nota registrada exitosamente.'
                    : 'Nota actualizada exitosamente.',
                'grade'  => $grade,
            ], $action === 'created' ? 201 : 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al registrar la nota.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Muestra el detalle de una nota específica.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $grade = Grade::with(['student.user', 'course', 'subject', 'period'])->findOrFail($id);
        $user  = $request->user();

        // Estudiante solo puede ver sus propias notas
        if ($user->isStudent() && $grade->student_id !== $user->student->id) {
            return response()->json(['message' => 'No tienes acceso a esta nota.'], 403);
        }

        // Docente solo puede ver notas de sus asignaciones
        if ($user->isTeacher()) {
            $hasAccess = $user->teacher->assignments()
                ->where('course_id', $grade->course_id)
                ->where('subject_id', $grade->subject_id)
                ->exists();

            if (! $hasAccess) {
                return response()->json(['message' => 'No tienes acceso a esta nota.'], 403);
            }
        }

        return response()->json(['grade' => $grade], 200);
    }

    /**
     * Actualiza solo el valor de la nota (y opcionalmente la competencia).
     */
    public function update(UpdateGradeRequest $request, int $id): JsonResponse
    {
        $grade = Grade::findOrFail($id);
        $user  = $request->user();

        // Verificar período abierto para no-admin
        if (! $user->isAdmin() && ! $grade->period->is_open) {
            return response()->json([
                'message' => 'El período está cerrado. No se puede modificar la nota.',
                'error'   => 'PERIOD_CLOSED',
            ], 422);
        }

        // Docente: verificar asignación
        if ($user->isTeacher()) {
            $hasAssignment = $user->teacher->assignments()
                ->where('course_id', $grade->course_id)
                ->where('subject_id', $grade->subject_id)
                ->exists();

            if (! $hasAssignment) {
                return response()->json(['message' => 'No tienes permiso para modificar esta nota.'], 403);
            }
        }

        DB::beginTransaction();

        try {
            $previousGrade = $grade->grade;
            $grade->update($request->only(['grade', 'competency']));

            $this->logAudit($user, $grade, 'updated', $previousGrade, $grade->grade);

            DB::commit();

            return response()->json([
                'message' => 'Nota actualizada exitosamente.',
                'grade'   => $grade->load(['student.user', 'subject', 'period']),
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar la nota.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Elimina una nota (solo en períodos abiertos para docentes).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $grade = Grade::with('period')->findOrFail($id);
        $user  = $request->user();

        if (! $user->isAdmin() && ! $grade->period->is_open) {
            return response()->json([
                'message' => 'El período está cerrado. No se puede eliminar la nota.',
                'error'   => 'PERIOD_CLOSED',
            ], 422);
        }

        if ($user->isTeacher()) {
            $hasAssignment = $user->teacher->assignments()
                ->where('course_id', $grade->course_id)
                ->where('subject_id', $grade->subject_id)
                ->exists();

            if (! $hasAssignment) {
                return response()->json(['message' => 'No tienes permiso para eliminar esta nota.'], 403);
            }
        }

        DB::beginTransaction();

        try {
            $this->logAudit($user, $grade, 'deleted', $grade->grade, null);
            $grade->delete();

            DB::commit();

            return response()->json(['message' => 'Nota eliminada exitosamente.'], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al eliminar la nota.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Devuelve todas las notas de un estudiante específico.
     *
     * Estudiante: solo puede ver las suyas.
     * Admin/Teacher: pueden ver las de cualquier estudiante (con restricción de asignación para teacher).
     */
    public function byStudent(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();

        // Estudiante solo puede consultar sus propias notas
        if ($user->isStudent()) {
            if (! $user->student || $user->student->id !== $studentId) {
                return response()->json(['message' => 'Solo puedes ver tus propias notas.'], 403);
            }
        }

        $grades = Grade::with(['course', 'subject', 'period'])
            ->where('student_id', $studentId)
            ->when($request->period_id, fn($q) => $q->where('period_id', $request->period_id))
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->orderBy('period_id')
            ->orderBy('subject_id')
            ->orderBy('competency')
            ->get();

        // Calcular promedio por asignatura y período
        $summary = $grades->groupBy(fn($g) => $g->period_id . '_' . $g->subject_id)
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'period'       => $first->period?->name ?? "Periodo {$first->period_id}",
                    'subject'      => $first->subject?->name ?? "Asignatura {$first->subject_id}",
                    'grades'       => $group->pluck('grade', 'competency'),
                    'average'      => round($group->avg('grade'), 2),
                ];
            })
            ->values();

        return response()->json([
            'student_id' => $studentId,
            'grades'     => $grades,
            'summary'    => $summary,
        ], 200);
    }

    /**
     * Genera un reporte de notas por curso y período.
     * Útil para planillas de calificaciones.
     */
    public function report(Request $request, int $courseId, int $periodId): JsonResponse
    {
        $user = $request->user();

        // Docente: verificar que tiene asignación en el curso
        if ($user->isTeacher()) {
            $subjectFilter = $request->subject_id;
            $hasAccess = $user->teacher->assignments()
                ->where('course_id', $courseId)
                ->when($subjectFilter, fn($q) => $q->where('subject_id', $subjectFilter))
                ->exists();

            if (! $hasAccess) {
                return response()->json(['message' => 'No tienes asignaciones en este curso.'], 403);
            }
        }

        $grades = Grade::with(['student.user', 'subject'])
            ->where('course_id', $courseId)
            ->where('period_id', $periodId)
            ->when($user->isStudent(), fn($q) => $q->where('student_id', $user->student?->id))
            ->when($request->subject_id, fn($q) => $q->where('subject_id', $request->subject_id))
            ->get();

        // Agrupar por estudiante → asignatura → competencia
        $report = $grades->groupBy('student_id')->map(function ($studentGrades) {
            $first   = $studentGrades->first();
            $student = $first->student;

            if (! $student) {
                return null;
            }

            return [
                'student' => [
                    'id'              => $student->id,
                    'full_name'       => $student->user->full_name,
                    'enrollment_code' => $student->enrollment_code,
                ],
                'subjects' => $studentGrades->groupBy('subject_id')->map(function ($subjectGrades) {
                    $subject = $subjectGrades->first()->subject;
                    return [
                        'subject'    => ['id' => $subject->id, 'name' => $subject->name],
                        'ser'        => optional($subjectGrades->firstWhere('competency', 'ser'))->grade,
                        'saber'      => optional($subjectGrades->firstWhere('competency', 'saber'))->grade,
                        'hacer'      => optional($subjectGrades->firstWhere('competency', 'hacer'))->grade,
                        'average'    => round($subjectGrades->avg('grade'), 2),
                    ];
                })->values(),
            ];
        })->filter()->values();

        return response()->json([
            'course_id'  => $courseId,
            'period_id'  => $periodId,
            'report'     => $report,
        ], 200);
    }

    /**
     * Registra una entrada en la bitácora de auditoría.
     *
     * @param  \App\Models\User   $user           Usuario que realizó la acción
     * @param  Grade              $grade          La nota afectada
     * @param  string             $action         created|updated|deleted
     * @param  float|null         $previousGrade  Valor anterior (null si es nueva)
     * @param  float|null         $newGrade       Valor nuevo (null si se eliminó)
     */
    private function logAudit($user, Grade $grade, string $action, ?float $previousGrade, ?float $newGrade): void
    {
        GradeAuditLog::create([
            'teacher_id'     => $user->isTeacher() ? $user->teacher->id : null,
            'student_id'     => $grade->student_id,
            'course_id'      => $grade->course_id,
            'subject_id'     => $grade->subject_id,
            'period_id'      => $grade->period_id,
            'competency'     => $grade->competency,
            'action'         => $action,
            'previous_grade' => $previousGrade,
            'new_grade'      => $newGrade,
        ]);
    }
}
