<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivitySubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_activity_id',
        'student_id',
        'file_path',
        'comment',
        'teacher_feedback',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function activity()
    {
        return $this->belongsTo(AcademicActivity::class, 'academic_activity_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
