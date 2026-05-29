<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: configuración global de la aplicación.
 *
 * Almacena pares clave-valor para parámetros del sistema
 * como el año lectivo activo y la cantidad de períodos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->string('value', 255);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Insertar valores por defecto al crear la tabla
        DB::table('app_settings')->insert([
            ['key' => 'current_year',  'value' => date('Y')],
            ['key' => 'period_count',  'value' => '4'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
