<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Course — grupo académico (ej: "Sexto A 2026").
 *
 * @property int    $id
 * @property string $name       Nombre descriptivo
 * @property string $grade      Identificador del grado ("6A")
 * @property int    $year       Año lectivo
 * @property bool   $is_active
 */
class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'grade',
        'year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'year'      => 'integer',
    ];

    // ─────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────

    /** Estudiantes inscritos en este curso */
    public function students(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Student::class);
    }

    /** Asignaciones docente-asignatura en este curso */
    public function teacherAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /** Docentes que tienen asignaciones en este curso */
    public function teachers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_assignments')
                    ->withTimestamps();
    }

    /** Notas registradas para este curso */
    public function grades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function subjects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
