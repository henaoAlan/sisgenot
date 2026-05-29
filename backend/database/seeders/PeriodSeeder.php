<?php

namespace Database\Seeders;

use App\Models\Period;
use Illuminate\Database\Seeder;

/**
 * PeriodSeeder — Genera los 4 períodos del año académico actual.
 *
 * Períodos creados:
 *   P1: Primer Período   (is_open = false, ya cerrado)
 *   P2: Segundo Período  (is_open = false, ya cerrado)
 *   P3: Tercer Período   (is_open = true,  en curso)
 *   P4: Cuarto Período   (is_open = false, aún no abierto)
 */
class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y');

        $periods = [
            ['ordering' => 1, 'name' => "Primer Período {$year}",   'is_open' => false],
            ['ordering' => 2, 'name' => "Segundo Período {$year}",  'is_open' => false],
            ['ordering' => 3, 'name' => "Tercer Período {$year}",   'is_open' => true],
            ['ordering' => 4, 'name' => "Cuarto Período {$year}",   'is_open' => false],
        ];

        foreach ($periods as $p) {
            Period::create(array_merge($p, [
                'year'       => $year,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info("4 períodos creados para el año {$year}.");
    }
}
