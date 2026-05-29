<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: asignaturas (materias).
 *
 * Catálogo de materias que pueden ser dictadas en cualquier
 * curso. Cada asignatura tiene un código único (ej: "MAT", "ESP").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);       // Nombre completo: "Matemáticas"
            $table->string('code', 20)->unique(); // Código corto: "MAT"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
