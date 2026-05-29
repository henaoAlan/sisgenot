<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_assignment_id',
        'student_id',
        'period_id',
        'moment',
        'activity_number',
        'title',
        'description',
        'due_date',
        'is_recovery',
        'file_path',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_recovery' => 'boolean',
    ];

    public function assignment()
    {
        return $this->belongsTo(TeacherAssignment::class, 'teacher_assignment_id');
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function grades()
    {
        return $this->hasMany(ActivityGrade::class);
    }

    public function submissions()
    {
        return $this->hasMany(ActivitySubmission::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function weight(): float
    {
        return match ((int) $this->moment) {
            1, 2 => 0.30 / 3,
            3 => 0.40 / 3,
            default => 0,
        };
    }
}
