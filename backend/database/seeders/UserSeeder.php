<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * UserSeeder — Crea usuarios de prueba para cada rol.
 *
 * Credenciales de prueba:
 * ─────────────────────────────────────────────
 * Admin:
 *   email:    admin@sisgenot.edu
 *   password: Admin1234
 *
 * Docentes:
 *   email:    matematicas@sisgenot.edu  / password: Teacher1234
 *   email:    lenguaje@sisgenot.edu     / password: Teacher1234
 *   email:    ciencias@sisgenot.edu     / password: Teacher1234
 *
 * Estudiantes:
 *   email:    est001@sisgenot.edu  / password: Student1234
 *   email:    est002@sisgenot.edu  / password: Student1234
 *   ... (20 estudiantes en total, 10 por curso)
 * ─────────────────────────────────────────────
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Administrador ──────────────────────────
        User::create([
            'email'     => 'admin@sisgenot.edu',
            'password'  => Hash::make('Admin1234'),
            'role'      => 'admin',
            'full_name' => 'Administrador del Sistema',
            'is_active' => true,
        ]);

        // ── Docentes ───────────────────────────────
        $teachers = [
            ['full_name' => 'Prof. Carlos Mendoza',   'email' => 'matematicas@sisgenot.edu', 'document_id' => 'DOC-T-001'],
            ['full_name' => 'Prof. Ana Rodríguez',    'email' => 'lenguaje@sisgenot.edu',    'document_id' => 'DOC-T-002'],
            ['full_name' => 'Prof. Juan Martínez',    'email' => 'ciencias@sisgenot.edu',    'document_id' => 'DOC-T-003'],
            ['full_name' => 'Prof. María González',   'email' => 'sociales@sisgenot.edu',    'document_id' => 'DOC-T-004'],
            ['full_name' => 'Prof. Luis Fernández',   'email' => 'artistica@sisgenot.edu',   'document_id' => 'DOC-T-005'],
        ];

        foreach ($teachers as $t) {
            $user = User::create([
                'email'     => $t['email'],
                'password'  => Hash::make('Teacher1234'),
                'role'      => 'teacher',
                'full_name' => $t['full_name'],
                'is_active' => true,
            ]);

            Teacher::create([
                'user_id'     => $user->id,
                'document_id' => $t['document_id'],
            ]);
        }

        // ── Estudiantes ────────────────────────────
        // Los course_id se asignan en CourseSeeder una vez que los cursos existen.
        // Aquí se crean con course_id null; el CourseSeeder los actualiza.
        $this->command->info('Usuarios creados. Los estudiantes serán asignados a cursos en CourseSeeder.');
    }
}
