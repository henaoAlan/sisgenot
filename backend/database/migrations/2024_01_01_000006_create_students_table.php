<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: perfil de estudiante.
 *
 * Extiende users con matrícula, documento y asignación de curso.
 * course_id puede ser NULL (estudiante sin curso asignado aún).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('document_id', 50)->unique();        // Documento de identidad
            $table->string('enrollment_code', 50)->unique();    // Código de matrícula
            $table->foreignId('course_id')
                  ->nullable()
                  ->constrained('courses')
                  ->nullOnDelete();                             // Al eliminar curso → NULL
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
