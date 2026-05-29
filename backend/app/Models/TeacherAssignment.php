<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo TeacherAssignment — asignación docente-curso-asignatura.
 *
 * Define qué docente imparte qué asignatura en qué curso.
 * La combinación (teacher_id, course_id, subject_id) es única.
 *
 * @property int $id
 * @property int $teacher_id
 * @property int $course_id
 * @property int $subject_id
 */
class TeacherAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'course_id',
        'subject_id',
    ];

    // ─────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────

    /** Docente dueño de esta asignación */
    public function teacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /** Curso de la asignación */
    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Asignatura de la asignación */
    public function subject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AcademicActivity::class);
    }
}
