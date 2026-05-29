<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Student — perfil extendido del estudiante.
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $document_id      Documento de identidad
 * @property string      $enrollment_code  Código de matrícula único
 * @property int|null    $course_id        Curso asignado (NULL si no inscrito)
 */
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_id',
        'enrollment_code',
        'course_id',
    ];

    // ─────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────

    /** Usuario base del estudiante */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Curso en el que está inscrito actualmente */
    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Todas las notas del estudiante */
    public function grades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /** Entradas de auditoría relacionadas con este estudiante */
    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GradeAuditLog::class);
    }

    public function activityGrades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActivityGrade::class);
    }

    public function submissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActivitySubmission::class);
    }

    public function observations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentObservation::class);
    }

    /** Docentes asignados a este estudiante */
    public function assignedTeachers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_student_assignments')
                    ->withTimestamps();
    }
}
