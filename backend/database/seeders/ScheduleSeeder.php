<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\TeacherAssignment;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            1 => ['07:00:00', '08:00:00'],
            2 => ['08:00:00', '09:00:00'],
            3 => ['09:00:00', '10:00:00'],
            4 => ['10:30:00', '11:30:00'],
            5 => ['11:30:00', '12:30:00'],
        ];

        $inserted = 0;

        TeacherAssignment::query()
            ->with(['course', 'subject', 'teacher.user'])
            ->orderBy('teacher_id')
            ->orderBy('course_id')
            ->get()
            ->groupBy('teacher_id')
            ->each(function ($assignments, $teacherId) use ($slots, &$inserted) {
                foreach ($assignments->values() as $index => $assignment) {
                    $day = ($index % 5) + 1;
                    [$startsAt, $endsAt] = $slots[$day];

                    $schedule = Schedule::firstOrCreate(
                        [
                            'course_id' => $assignment->course_id,
                            'subject_id' => $assignment->subject_id,
                            'teacher_id' => $teacherId,
                            'day_of_week' => $day,
                            'starts_at' => $startsAt,
                            'ends_at' => $endsAt,
                        ],
                        [
                            'classroom' => 'Aula ' . str_pad((string) $assignment->course_id, 2, '0', STR_PAD_LEFT),
                        ]
                    );

                    if ($schedule->wasRecentlyCreated) {
                        $inserted++;
                    }
                }
            });

        $this->command->info("{$inserted} horarios creados.");
    }
}
