<?php

namespace App\Services;

use App\Models\ActivityGrade;
use App\Models\Student;
use Illuminate\Support\Collection;

class AcademicMetrics
{
    public function studentAverage(Student $student): float
    {
        $grades = $student->activityGrades()->with('activity')->whereNotNull('grade')->get();

        if ($grades->isEmpty()) {
            return 0;
        }

        $grouped = $grades->groupBy(fn (ActivityGrade $grade) => $grade->activity->teacher_assignment_id);
        $subjectAverages = $grouped->map(fn (Collection $items) => $this->weightedAverage($items));

        return round($subjectAverages->avg() ?: 0, 2);
    }

    public function weightedAverage(Collection $grades): float
    {
        $total = 0;
        $weight = 0;

        foreach ($grades as $grade) {
            if ($grade->grade === null || ! $grade->activity) {
                continue;
            }

            $activityWeight = $grade->activity->weight();
            $total += (float) $grade->grade * $activityWeight;
            $weight += $activityWeight;
        }

        return $weight > 0 ? round($total / $weight, 2) : 0;
    }

    public function groupAverage(int $courseId): float
    {
        $students = Student::where('course_id', $courseId)->get();

        if ($students->isEmpty()) {
            return 0;
        }

        return round($students->map(fn (Student $student) => $this->studentAverage($student))->avg() ?: 0, 2);
    }
}
