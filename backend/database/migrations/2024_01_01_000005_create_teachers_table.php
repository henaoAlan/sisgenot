<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: perfil de docente.
 *
 * Extiende la tabla users con datos específicos del docente.
 * La FK a users tiene CASCADE para eliminar el perfil si se
 * elimina el usuario base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->unique()                   // Un usuario → un perfil docente
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('document_id', 50)->unique(); // Cédula / NIT del docente
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
