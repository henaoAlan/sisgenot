<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Subject — asignatura del catálogo académico.
 *
 * @property int    $id
 * @property string $name  Nombre completo ("Matemáticas")
 * @property string $code  Código corto único ("MAT")
 */
class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'course_id',
    ];

    // ─────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────

    /** Asignaciones docente-curso que implican esta asignatura */
    public function teacherAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /** Docentes que dictan esta asignatura (en distintos cursos) */
    public function teachers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_assignments')
                    ->withTimestamps();
    }

    /** Notas registradas para esta asignatura */
    public function grades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
