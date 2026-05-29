<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\GradeAuditController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\PeriodController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentObservationController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherAssignmentController;
use App\Http\Controllers\Api\TeacherStudentAssignmentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — sisgenot
|--------------------------------------------------------------------------
|
| Todos los endpoints están versionados bajo /api (ver bootstrap/app.php).
| Las rutas protegidas requieren token Sanctum en el header:
|   Authorization: Bearer <token>
|
| Middleware de roles se aplica via:
|   ->middleware('role:admin')
|   ->middleware('role:admin,teacher')
|
*/

// ─────────────────────────────────────────────
// Rutas públicas — Autenticación
// ─────────────────────────────────────────────
Route::prefix('auth')->name('auth.')->group(function () {

    /** POST /api/auth/login — Obtener token de acceso */
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

});

// ─────────────────────────────────────────────
// Rutas protegidas — Requieren token Sanctum
// ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ──────────────────────────────────
    Route::prefix('auth')->name('auth.')->group(function () {

        /** POST /api/auth/logout — Revocar token */
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        /** GET /api/auth/me — Perfil del usuario autenticado */
        Route::get('me', [AuthController::class, 'me'])->name('me');

    });

    // ── Usuarios (solo admin) ─────────────────
    Route::middleware('role:admin')
        ->prefix('users')
        ->name('users.')
        ->group(function () {
            Route::get('/',         [UserController::class, 'index'])->name('index');
            Route::post('/',        [UserController::class, 'store'])->name('store');
            Route::get('/{user}',   [UserController::class, 'show'])->name('show');
            Route::put('/{user}',   [UserController::class, 'update'])->name('update');
            Route::delete('/{user}',[UserController::class, 'destroy'])->name('destroy');
        });

    // ── Asignación de estudiantes a docentes ────────────────────────────────
    Route::prefix('teacher-student-assignments')
        ->name('teacher-student-assignments.')
        ->group(function () {
            /** GET /api/teacher-student-assignments/teacher/{id}/students — Estudiantes de un docente */
            Route::get('/teacher/{teacher}/students', [TeacherStudentAssignmentController::class, 'studentsByTeacher'])
                ->middleware('role:admin,teacher')
                ->name('teacher.students');

            /** GET /api/teacher-student-assignments/student/{id}/teachers — Docentes de un estudiante */
            Route::get('/student/{student}/teachers', [TeacherStudentAssignmentController::class, 'teachersByStudent'])
                ->middleware('role:admin,teacher')
                ->name('student.teachers');

            Route::middleware('role:admin')->group(function () {
                /** GET /api/teacher-student-assignments/teachers — Todos los docentes */
                Route::get('/teachers', [TeacherStudentAssignmentController::class, 'availableTeachers'])->name('teachers');

                /** GET /api/teacher-student-assignments/students — Todos los estudiantes */
                Route::get('/students', [TeacherStudentAssignmentController::class, 'availableStudents'])->name('students');

                /** POST /api/teacher-student-assignments/assign — Asignar un estudiante a un docente */
                Route::post('/assign', [TeacherStudentAssignmentController::class, 'assign'])->name('assign');

                /** POST /api/teacher-student-assignments/assign-multiple — Asignar múltiples estudiantes a un docente */
                Route::post('/assign-multiple', [TeacherStudentAssignmentController::class, 'assignMultiple'])->name('assign-multiple');

                /** POST /api/teacher-student-assignments/unassign — Desasignar un estudiante de un docente */
                Route::post('/unassign', [TeacherStudentAssignmentController::class, 'unassign'])->name('unassign');
            });
        });

    // ── Cursos ────────────────────────────────
    Route::prefix('courses')->name('courses.')->group(function () {

        /** GET /api/courses — Admin ve todo; teacher sus cursos; student el suyo */
        Route::get('/', [CourseController::class, 'index'])
            ->middleware('role:admin,teacher,student')
            ->name('index');

        /** POST /api/courses — Crear curso (admin) */
        Route::post('/', [CourseController::class, 'store'])
            ->middleware('role:admin')
            ->name('store');

        /** GET /api/courses/{id} */
        Route::get('/{course}', [CourseController::class, 'show'])
            ->middleware('role:admin,teacher,student')
            ->name('show');

        /** PUT /api/courses/{id} */
        Route::put('/{course}', [CourseController::class, 'update'])
            ->middleware('role:admin')
            ->name('update');

        /** DELETE /api/courses/{id} */
        Route::delete('/{course}', [CourseController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('destroy');

        /** GET /api/courses/{id}/students — Estudiantes del curso */
        Route::get('/{course}/students', [CourseController::class, 'students'])
            ->middleware('role:admin,teacher')
            ->name('students');

    });

    // ── Asignaturas ───────────────────────────
    Route::prefix('subjects')->name('subjects.')->group(function () {

        Route::get('/', [SubjectController::class, 'index'])
            ->middleware('role:admin,teacher,student')
            ->name('index');

        Route::post('/', [SubjectController::class, 'store'])
            ->middleware('role:admin')
            ->name('store');

        Route::get('/{subject}', [SubjectController::class, 'show'])
            ->middleware('role:admin,teacher,student')
            ->name('show');

        Route::put('/{subject}', [SubjectController::class, 'update'])
            ->middleware('role:admin')
            ->name('update');

        Route::delete('/{subject}', [SubjectController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('destroy');

    });

    // ── Períodos ──────────────────────────────
    Route::prefix('periods')->name('periods.')->group(function () {

        Route::get('/', [PeriodController::class, 'index'])
            ->middleware('role:admin,teacher,student')
            ->name('index');

        Route::post('/', [PeriodController::class, 'store'])
            ->middleware('role:admin')
            ->name('store');

        Route::get('/{period}', [PeriodController::class, 'show'])
            ->middleware('role:admin,teacher,student')
            ->name('show');

        Route::put('/{period}', [PeriodController::class, 'update'])
            ->middleware('role:admin')
            ->name('update');

        Route::delete('/{period}', [PeriodController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('destroy');

        /** PATCH /api/periods/{id}/toggle — Abrir/cerrar período */
        Route::patch('/{period}/toggle', [PeriodController::class, 'toggle'])
            ->middleware('role:admin')
            ->name('toggle');

    });

    // ── Asignaciones docente-curso-asignatura ─
    Route::prefix('teacher-assignments')->name('teacher-assignments.')->group(function () {

        Route::get('/', [TeacherAssignmentController::class, 'index'])
            ->middleware('role:admin,teacher')
            ->name('index');

        Route::post('/', [TeacherAssignmentController::class, 'store'])
            ->middleware('role:admin')
            ->name('store');

        Route::put('/{assignment}', [TeacherAssignmentController::class, 'update'])
            ->middleware('role:admin')
            ->name('update');

        Route::delete('/{assignment}', [TeacherAssignmentController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('destroy');

    });

    Route::get('/students', [StudentController::class, 'index'])
        ->middleware('role:admin,teacher,student')
        ->name('students.index');

    // ── Notas (core del sistema) ──────────────
    Route::prefix('grades')->name('grades.')->group(function () {

        /** GET /api/grades — Lista filtrada por rol */
        Route::get('/', [GradeController::class, 'index'])
            ->middleware('role:admin,teacher,student')
            ->name('index');

        /** POST /api/grades — Crear/actualizar nota (upsert) */
        Route::post('/', [GradeController::class, 'store'])
            ->middleware('role:admin,teacher')
            ->name('store');

        /**
         * Rutas específicas ANTES de /{grade} para evitar conflictos de binding.
         */

        /** GET /api/grades/student/{studentId} */
        Route::get('/student/{studentId}', [GradeController::class, 'byStudent'])
            ->middleware('role:admin,teacher,student')
            ->name('by-student');

        /** GET /api/grades/report/{courseId}/{periodId} */
        Route::get('/report/{courseId}/{periodId}', [GradeController::class, 'report'])
            ->middleware('role:admin,teacher,student')
            ->name('report');

        /** GET /api/grades/{id} */
        Route::get('/{grade}', [GradeController::class, 'show'])
            ->middleware('role:admin,teacher,student')
            ->name('show');

        /** PUT /api/grades/{id} */
        Route::put('/{grade}', [GradeController::class, 'update'])
            ->middleware('role:admin,teacher')
            ->name('update');

        /** DELETE /api/grades/{id} */
        Route::delete('/{grade}', [GradeController::class, 'destroy'])
            ->middleware('role:admin,teacher')
            ->name('destroy');

    });

    // ── Bitácora de auditoría ─────────────────
    Route::prefix('audit-log')->name('audit-log.')->group(function () {

        /** GET /api/audit-log — Historial completo (admin) */
        Route::get('/', [GradeAuditController::class, 'index'])
            ->middleware('role:admin,teacher')
            ->name('index');

        /** GET /api/audit-log/student/{id} */
        Route::get('/student/{studentId}', [GradeAuditController::class, 'byStudent'])
            ->middleware('role:admin,teacher')
            ->name('by-student');

        /** GET /api/audit-log/course/{id} */
        Route::get('/course/{courseId}', [GradeAuditController::class, 'byCourse'])
            ->middleware('role:admin,teacher')
            ->name('by-course');

    });

    // Observaciones estudiantiles
    Route::prefix('observations')->name('observations.')->group(function () {
        Route::get('/', [StudentObservationController::class, 'index'])
            ->middleware('role:admin,teacher,student')
            ->name('index');
        Route::post('/', [StudentObservationController::class, 'store'])
            ->middleware('role:admin,teacher')
            ->name('store');
        Route::get('/{observation}', [StudentObservationController::class, 'show'])
            ->middleware('role:admin,teacher,student')
            ->name('show');
        Route::put('/{observation}', [StudentObservationController::class, 'update'])
            ->middleware('role:admin,teacher')
            ->name('update');
        Route::delete('/{observation}', [StudentObservationController::class, 'destroy'])
            ->middleware('role:admin,teacher')
            ->name('destroy');
    });

    // Horarios
    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])
            ->middleware('role:admin,teacher,student')
            ->name('index');
        Route::post('/', [ScheduleController::class, 'store'])
            ->middleware('role:admin')
            ->name('store');
        Route::get('/{schedule}', [ScheduleController::class, 'show'])
            ->middleware('role:admin,teacher,student')
            ->name('show');
        Route::put('/{schedule}', [ScheduleController::class, 'update'])
            ->middleware('role:admin')
            ->name('update');
        Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('destroy');
    });

});
