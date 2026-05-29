<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Orquesta todos los seeders en el orden correcto.
 *
 * Orden de ejecución (respeta las FK):
 *   1. UserSeeder    → Usuarios base (admin + docentes sin perfiles de estudiante)
 *   2. SubjectSeeder → Catálogo de asignaturas
 *   3. CourseSeeder  → Cursos + estudiantes
 *   4. PeriodSeeder  → Períodos académicos
 *   5. GradeSeeder   → Asignaciones docentes + notas de ejemplo
 *
 * Ejecutar con:
 *   php artisan migrate:fresh --seed
 *   php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SubjectSeeder::class,
            CourseSeeder::class,
            PeriodSeeder::class,
            GradeSeeder::class,
            ScheduleSeeder::class,
        ]);

        $this->command->info('✅ Sisgenot — Base de datos poblada exitosamente.');
        $this->command->line('');
        $this->command->line('Credenciales de acceso:');
        $this->command->line('  Admin:    admin@sisgenot.edu         / Admin1234');
        $this->command->line('  Docente:  matematicas@sisgenot.edu   / Teacher1234');
        $this->command->line('  Estudiante: est001@sisgenot.edu      / Student1234');
    }
}
