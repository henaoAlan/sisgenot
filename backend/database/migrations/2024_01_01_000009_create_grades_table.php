<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: tabla de notas (grades).
 *
 * Una fila representa la nota de UN estudiante en UNA asignatura,
 * periodo y competencia específica (ser / saber / hacer).
 * El rango válido es 1.00 – 5.00 (validado a nivel aplicación y DB).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();
            $table->foreignId('course_id')
                  ->constrained('courses')
                  ->cascadeOnDelete();
            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();
            $table->foreignId('period_id')
                  ->constrained('periods')
                  ->cascadeOnDelete();
            $table->enum('competency', ['ser', 'saber', 'hacer']);
            $table->decimal('grade', 3, 2);  // 1.00 – 5.00
            $table->timestamps();

            // Una nota única por (estudiante, curso, asignatura, periodo, competencia)
            $table->unique(
                ['student_id', 'course_id', 'subject_id', 'period_id', 'competency'],
                'uq_grades'
            );

            // Índices de rendimiento
            $table->index('student_id', 'idx_grades_student');
            $table->index('period_id',  'idx_grades_period');
            $table->index(
                ['student_id', 'course_id', 'subject_id', 'period_id'],
                'idx_grades_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
