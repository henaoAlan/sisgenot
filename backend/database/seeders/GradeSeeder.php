<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Grade;
use App\Models\GradeAuditLog;
use App\Models\Period;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Database\Seeder;

/**
 * GradeSeeder — Crea asignaciones docentes y notas de ejemplo.
 *
 * Flujo:
 *   1. Asigna cada docente a cursos y asignaturas
 *   2. Genera notas ficticias para los dos primeros períodos (cerrados)
 *      para todos los estudiantes de los cursos asignados
 *
 * Las notas del período activo (P3) se dejan vacías para probar
 * la creación via API.
 */
class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y');

        $teachers  = Teacher::all()->keyBy(fn($t) => $t->document_id);
        $courses   = Course::where('year', $year)->get()->keyBy('grade');
        $subjects  = Subject::all()->keyBy('code');
        $periods   = Period::where('year', $year)->orderBy('ordering')->get();

        $course6A  = $courses['6A'];
        $course7B  = $courses['7B'];
        $course8C  = $courses['8C'];

        $subjectIds = [
            'MAT' => $subjects['MAT']->id,
            'LEN' => $subjects['LEN']->id,
            'CNA' => $subjects['CNA']->id,
            'CSO' => $subjects['CSO']->id,
            'ART' => $subjects['ART']->id,
        ];

        // ── 1. Asignaciones docente-curso-asignatura ──
        $assignments = [
            // Prof. Mendoza → Matemáticas en los 3 cursos
            ['teacher' => $teachers['DOC-T-001'], 'course' => $course6A, 'subject' => $subjects['MAT']],
            ['teacher' => $teachers['DOC-T-001'], 'course' => $course7B, 'subject' => $subjects['MAT']],
            ['teacher' => $teachers['DOC-T-001'], 'course' => $course8C, 'subject' => $subjects['MAT']],

            // Prof. Rodríguez → Lenguaje en 6A y 7B
            ['teacher' => $teachers['DOC-T-002'], 'course' => $course6A, 'subject' => $subjects['LEN']],
            ['teacher' => $teachers['DOC-T-002'], 'course' => $course7B, 'subject' => $subjects['LEN']],

            // Prof. Martínez → Ciencias Naturales en 6A y 8C
            ['teacher' => $teachers['DOC-T-003'], 'course' => $course6A, 'subject' => $subjects['CNA']],
            ['teacher' => $teachers['DOC-T-003'], 'course' => $course8C, 'subject' => $subjects['CNA']],

            // Prof. González → Ciencias Sociales en 7B y 8C
            ['teacher' => $teachers['DOC-T-004'], 'course' => $course7B, 'subject' => $subjects['CSO']],
            ['teacher' => $teachers['DOC-T-004'], 'course' => $course8C, 'subject' => $subjects['CSO']],

            // Prof. Fernández → Artística en los 3 cursos
            ['teacher' => $teachers['DOC-T-005'], 'course' => $course6A, 'subject' => $subjects['ART']],
            ['teacher' => $teachers['DOC-T-005'], 'course' => $course7B, 'subject' => $subjects['ART']],
            ['teacher' => $teachers['DOC-T-005'], 'course' => $course8C, 'subject' => $subjects['ART']],
        ];

        foreach ($assignments as $a) {
            TeacherAssignment::firstOrCreate([
                'teacher_id' => $a['teacher']->id,
                'course_id'  => $a['course']->id,
                'subject_id' => $a['subject']->id,
            ]);
        }

        $this->command->info(count($assignments) . ' asignaciones docentes creadas.');

        // ── 2. Notas para períodos 1 y 2 (cerrados) ──
        $closedPeriods  = $periods->filter(fn($p) => ! $p->is_open)->take(2);
        $competencies   = ['ser', 'saber', 'hacer'];
        $gradesInserted = 0;

        foreach ($assignments as $a) {
            $students = Student::where('course_id', $a['course']->id)->get();

            foreach ($students as $student) {
                foreach ($closedPeriods as $period) {
                    foreach ($competencies as $competency) {
                        // Nota aleatoria entre 2.5 y 5.0 (1 decimal)
                        $gradeValue = round(mt_rand(25, 50) / 10, 2);

                        Grade::firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'course_id'  => $a['course']->id,
                                'subject_id' => $a['subject']->id,
                                'period_id'  => $period->id,
                                'competency' => $competency,
                            ],
                            ['grade' => $gradeValue]
                        );

                        // Registrar en auditoría (como si el docente las hubiera ingresado)
                        GradeAuditLog::create([
                            'teacher_id'     => $a['teacher']->id,
                            'student_id'     => $student->id,
                            'course_id'      => $a['course']->id,
                            'subject_id'     => $a['subject']->id,
                            'period_id'      => $period->id,
                            'competency'     => $competency,
                            'action'         => 'created',
                            'previous_grade' => null,
                            'new_grade'      => $gradeValue,
                        ]);

                        $gradesInserted++;
                    }
                }
            }
        }

        $this->command->info("{$gradesInserted} notas de ejemplo insertadas.");
    }
}
