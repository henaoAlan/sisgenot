<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_activities', 'student_id')) {
                $table->foreignId('student_id')
                    ->nullable()
                    ->after('teacher_assignment_id')
                    ->constrained('students')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_activities', function (Blueprint $table) {
            if (Schema::hasColumn('academic_activities', 'student_id')) {
                $table->dropConstrainedForeignId('student_id');
            }
        });
    }
};
