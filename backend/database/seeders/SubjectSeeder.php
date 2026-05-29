<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * SubjectSeeder — Asignaturas del currículo escolar colombiano (ejemplo).
 */
class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Matemáticas',                 'code' => 'MAT'],
            ['name' => 'Lengua Castellana',           'code' => 'LEN'],
            ['name' => 'Ciencias Naturales',          'code' => 'CNA'],
            ['name' => 'Ciencias Sociales',           'code' => 'CSO'],
            ['name' => 'Educación Artística',         'code' => 'ART'],
            ['name' => 'Educación Física',            'code' => 'EDF'],
            ['name' => 'Ética y Valores',             'code' => 'ETI'],
            ['name' => 'Inglés',                      'code' => 'ING'],
            ['name' => 'Tecnología e Informática',    'code' => 'TEC'],
            ['name' => 'Religión',                    'code' => 'REL'],
        ];

        foreach ($subjects as $subject) {
            Subject::create(array_merge($subject, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info(count($subjects) . ' asignaturas creadas.');
    }
}
