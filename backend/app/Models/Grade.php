<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Grade — nota académica.
 *
 * Representa la nota de un estudiante en una asignatura,
 * período y competencia específica (ser / saber / hacer).
 * Rango válido: 1.00 – 5.00.
 *
 * @property int    $id
 * @property int    $student_id
 * @property int    $course_id
 * @property int    $subject_id
 * @property int    $period_id
 * @property string $competency  ser|saber|hacer
 * @property float  $grade
 */
class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'subject_id',
        'period_id',
        'competency',
        'grade',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
    ];

    // ─────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function period(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    // ─────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────

    /** Filtra notas por período */
    public function scopeForPeriod($query, int $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    /** Filtra notas por curso */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }
}
