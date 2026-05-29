<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Teacher — perfil extendido del docente.
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $document_id  Número de cédula/NIT
 */
class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_id',
    ];

    // ─────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────

    /** Usuario base asociado al docente */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Asignaciones de este docente (curso + asignatura).
     * Un docente puede dictar múltiples asignaturas en varios cursos.
     */
    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /** Cursos en los que el docente tiene alguna asignación */
    public function courses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'teacher_assignments')
                    ->withTimestamps();
    }

    /** Asignaturas que el docente dicta (puede repetirse en varios cursos) */
    public function subjects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_assignments')
                    ->withTimestamps();
    }

    /** Entradas en la bitácora de auditoría de este docente */
    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GradeAuditLog::class);
    }

    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function observations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentObservation::class);
    }

    /** Estudiantes asignados a este docente */
    public function assignedStudents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'teacher_student_assignments')
                    ->withTimestamps();
    }
}
