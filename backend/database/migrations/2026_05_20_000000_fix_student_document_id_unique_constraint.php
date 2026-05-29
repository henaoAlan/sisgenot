<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remover el índice UNIQUE de document_id
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['document_id']);
        });
    }

    public function down(): void
    {
        // Restaurar el índice UNIQUE (solo si es necesario revertir)
        Schema::table('students', function (Blueprint $table) {
            $table->unique('document_id');
        });
    }
};
