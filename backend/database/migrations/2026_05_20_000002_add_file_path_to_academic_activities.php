<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_activities', 'file_path')) {
                $table->string('file_path')->nullable()->after('due_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_activities', function (Blueprint $table) {
            if (Schema::hasColumn('academic_activities', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }
};
