<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: cursos / grupos académicos.
 *
 * Un curso es la combinación de grado + año, por ejemplo "6A 2026".
 * Los estudiantes se inscriben a un curso y los docentes
 * son asignados a un curso + asignatura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);          // Nombre descriptivo: "Sexto A"
            $table->string('grade', 20);          // Identificador del grado: "6A"
            $table->unsignedSmallInteger('year'); // Año lectivo: 2026
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
