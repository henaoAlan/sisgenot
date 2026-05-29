<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Period — período académico.
 *
 * Cada año tiene N períodos ordenados (por defecto 4).
 * Solo los períodos con is_open = true aceptan nuevas notas.
 *
 * @property int    $id
 * @property int    $year
 * @property int    $ordering  Orden dentro del año (1-4)
 * @property string $name      Nombre descriptivo
 * @property bool   $is_open   Período abierto para notas
 */
class Period extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'ordering',
        'name',
        'is_open',
    ];

    protected $casts = [
        'is_open'  => 'boolean',
        'year'     => 'integer',
        'ordering' => 'integer',
    ];

    // ─────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────

    /** Notas registradas en este período */
    public function grades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /** Entradas de auditoría de este período */
    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GradeAuditLog::class);
    }
}
