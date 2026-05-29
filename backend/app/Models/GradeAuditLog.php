<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo GradeAuditLog — bitácora de cambios de notas.
 *
 * Registro inmutable: no tiene updated_at.
 * Cada entrada documenta quién cambió qué nota y cómo.
 *
 * @property int         $id
 * @property int|null    $teacher_id      NULL si fue admin
 * @property int         $student_id
 * @property int         $course_id
 * @property int         $subject_id
 * @property int         $period_id
 * @property string      $competency      ser|saber|hacer
 * @property string      $action          created|updated|deleted
 * @property float|null  $previous_grade
 * @property float|null  $new_grade
 */
class GradeAuditLog extends Model
{
    // Sin updated_at — registro inmutable
    const UPDATED_AT = null;

    protected $table = 'grade_audit_log';

    protected $fillable = [
        'teacher_id',
        'student_id',
        'course_id',
        'subject_id',
        'period_id',
        'competency',
        'action',
        'previous_grade',
        'new_grade',
    ];

    protected $casts = [
        'previous_grade' => 'decimal:2',
        'new_grade'      => 'decimal:2',
        'created_at'     => 'datetime',
    ];

    // ─────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────

    /** Docente que realizó la acción (puede ser NULL para admin) */
    public function teacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

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
}
