<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_submissions') && Schema::hasColumn('activity_submissions', 'title')) {
            DB::statement('ALTER TABLE activity_submissions MODIFY title varchar(255) NULL');
        }
    }

    public function down(): void
    {
        // This migration is intended to fix legacy schema mismatch for activity submissions.
    }
};
