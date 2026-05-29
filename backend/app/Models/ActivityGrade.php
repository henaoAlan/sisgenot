<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_activity_id',
        'student_id',
        'grade',
        'feedback',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
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
