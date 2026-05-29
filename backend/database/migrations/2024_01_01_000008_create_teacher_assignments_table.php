<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: asignaciones docente → curso → asignatura.
 *
 * Define qué docente imparte qué asignatura en qué curso.
 * La combinación (teacher_id, course_id, subject_id) es única
 * para evitar asignaciones duplicadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')
                  ->constrained('teachers')
                  ->cascadeOnDelete();
            $table->foreignId('course_id')
                  ->constrained('courses')
                  ->cascadeOnDelete();
            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['teacher_id', 'course_id', 'subject_id'],
                'uq_teacher_assignments'
            );
        });

        // Índice de rendimiento para búsquedas por docente
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->index('teacher_id', 'idx_teacher_assignments_teacher');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
    }
};
