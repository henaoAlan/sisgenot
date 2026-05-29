<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: tabla de usuarios base del sistema sisgenot.
 *
 * Centraliza la identidad de todos los actores del sistema
 * (admin, teacher, student). Los perfiles específicos de
 * docente y estudiante viven en sus propias tablas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->string('password');                          // Manejado por Laravel Hash
            $table->enum('role', ['admin', 'teacher', 'student']);
            $table->string('full_name', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();                                // created_at + updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
