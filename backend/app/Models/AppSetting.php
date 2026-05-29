<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo AppSetting — configuración global del sistema.
 *
 * Tabla de clave-valor para parámetros del sistema:
 *   - current_year  : año lectivo activo
 *   - period_count  : número de períodos por año
 *
 * @property string $key
 * @property string $value
 */
class AppSetting extends Model
{
    protected $table      = 'app_settings';
    protected $primaryKey = 'key';
    protected $keyType    = 'string';

    // Sin auto-increment ni updated_at estándar
    public $incrementing = false;
    const UPDATED_AT     = null;

    protected $fillable = ['key', 'value'];

    /**
     * Obtiene el valor de una clave de configuración.
     *
     * @param  string      $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null): mixed
    {
        $setting = static::find($key);
        return $setting ? $setting->value : $default;
    }

    /**
     * Establece o actualiza el valor de una clave.
     *
     * @param string $key
     * @param string $value
     */
    public static function setValue(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
