<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: bitácora de auditoría de cambios de notas.
 *
 * Registro INMUTABLE de cada operación sobre las notas.
 * teacher_id puede ser NULL si la acción fue realizada por admin
 * cuyo perfil no está en la tabla teachers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')
                  ->nullable()
                  ->constrained('teachers')
                  ->nullOnDelete();
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
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->decimal('previous_grade', 3, 2)->nullable();
            $table->decimal('new_grade', 3, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();      // Solo lectura, sin updated_at

            // Índice compuesto para reportes por curso, periodo y fecha
            $table->index(
                ['course_id', 'period_id', 'created_at'],
                'idx_grade_audit_course_period'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_audit_log');
    }
};
