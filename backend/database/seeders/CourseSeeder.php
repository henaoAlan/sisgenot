<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * CourseSeeder — Crea cursos de prueba y sus estudiantes.
 *
 * Genera 3 cursos para el año actual y popula cada uno
 * con 10 estudiantes ficticios listos para pruebas.
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y');

        // ── Cursos ─────────────────────────────────
        $courses = Course::insert([
            ['name' => 'Sexto A',    'grade' => '6A', 'year' => $year, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Séptimo B',  'grade' => '7B', 'year' => $year, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Octavo C',   'grade' => '8C', 'year' => $year, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $course1 = Course::where('grade', '6A')->where('year', $year)->first();
        $course2 = Course::where('grade', '7B')->where('year', $year)->first();
        $course3 = Course::where('grade', '8C')->where('year', $year)->first();

        // ── Estudiantes (10 por curso = 30 total) ──
        $studentData = [
            // Sexto A
            ['full_name' => 'Sofía Pérez Torres',      'course' => $course1],
            ['full_name' => 'Mateo García López',       'course' => $course1],
            ['full_name' => 'Valentina Cruz Herrera',   'course' => $course1],
            ['full_name' => 'Santiago Díaz Morales',    'course' => $course1],
            ['full_name' => 'Isabella Rojas Vargas',    'course' => $course1],
            ['full_name' => 'Sebastián Ramírez Nieto',  'course' => $course1],
            ['full_name' => 'Camila Torres Suárez',     'course' => $course1],
            ['full_name' => 'Daniel Flores Castro',     'course' => $course1],
            ['full_name' => 'Lucía Moreno Ríos',        'course' => $course1],
            ['full_name' => 'Emilio Herrera Soto',      'course' => $course1],

            // Séptimo B
            ['full_name' => 'Mariana Jiménez Vega',    'course' => $course2],
            ['full_name' => 'Alejandro Ruiz Blanco',   'course' => $course2],
            ['full_name' => 'Paula Morales Fuentes',   'course' => $course2],
            ['full_name' => 'Diego Sánchez Paredes',   'course' => $course2],
            ['full_name' => 'Natalia Vargas Mendoza',  'course' => $course2],
            ['full_name' => 'Andrés Castro Guerrero',  'course' => $course2],
            ['full_name' => 'Laura Salinas Mora',      'course' => $course2],
            ['full_name' => 'Julián Núñez Arroyo',    'course' => $course2],
            ['full_name' => 'Gabriela Ortega Peña',   'course' => $course2],
            ['full_name' => 'Tomás Vega Delgado',      'course' => $course2],

            // Octavo C
            ['full_name' => 'Carolina Reyes Espinoza', 'course' => $course3],
            ['full_name' => 'Rodrigo Aguilar Quispe',  'course' => $course3],
            ['full_name' => 'Ana Villanueva Soria',     'course' => $course3],
            ['full_name' => 'Felipe Carrasco Tapia',    'course' => $course3],
            ['full_name' => 'Paola Quispe Mamani',      'course' => $course3],
            ['full_name' => 'Roberto Gutiérrez Huanca', 'course' => $course3],
            ['full_name' => 'Vanessa Colque Ticona',    'course' => $course3],
            ['full_name' => 'Cristian Mamani Condori',  'course' => $course3],
            ['full_name' => 'Fernanda Ticona Huanca',   'course' => $course3],
            ['full_name' => 'Marcos Condori Apaza',     'course' => $course3],
        ];

        foreach ($studentData as $index => $data) {
            $emailNum = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $docNum   = str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            $user = User::create([
                'email'     => "est{$emailNum}@sisgenot.edu",
                'password'  => \Illuminate\Support\Facades\Hash::make('Student1234'),
                'role'      => 'student',
                'full_name' => $data['full_name'],
                'is_active' => true,
            ]);

            Student::create([
                'user_id'         => $user->id,
                'document_id'     => "DOC-S-{$docNum}",
                'enrollment_code' => "MAT-{$year}-{$docNum}",
                'course_id'       => $data['course']->id,
            ]);
        }

        $this->command->info('30 estudiantes creados y asignados a cursos.');
    }
}
