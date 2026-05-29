<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: períodos académicos.
 *
 * Cada año tiene N períodos ordenados (por defecto 4).
 * El campo is_open controla si el período acepta nuevas notas.
 * La combinación (year, ordering) es única.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('ordering');  // 1, 2, 3, 4
            $table->string('name', 50);               // "Primer Período 2026"
            $table->boolean('is_open')->default(true);
            $table->timestamps();

            $table->unique(['year', 'ordering'], 'uq_periods_year_ordering');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
