<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_submissions', 'comment')) {
                $table->text('comment')->nullable()->after('file_path');
            }

            if (! Schema::hasColumn('activity_submissions', 'teacher_feedback')) {
                $table->text('teacher_feedback')->nullable()->after(
                    Schema::hasColumn('activity_submissions', 'comment') ? 'comment' : 'file_path'
                );
            }

            if (! Schema::hasColumn('activity_submissions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after(
                    Schema::hasColumn('activity_submissions', 'submitted_at') ? 'submitted_at' : 'teacher_feedback'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('activity_submissions', 'teacher_feedback')) {
                $table->dropColumn('teacher_feedback');
            }

            if (Schema::hasColumn('activity_submissions', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
        });
    }
};
