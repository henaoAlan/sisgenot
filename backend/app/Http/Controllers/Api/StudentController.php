<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Student::with(['user', 'course'])
            ->when($request->course_id, fn ($q) => $q->where('course_id', $request->course_id))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->whereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('full_name', 'like', "%{$request->search}%")
                            ->orWhere('email', 'like', "%{$request->search}%");
                    })->orWhere('enrollment_code', 'like', "%{$request->search}%");
                });
            });

        if ($user->isTeacher()) {
            $teacherId = $user->teacher->id;
            $query->whereHas('course.teacherAssignments', fn ($q) => $q->where('teacher_id', $teacherId));
        }

        if ($user->isStudent()) {
            $query->where('students.id', $user->student->id);
        }

        return response()->json([
            'data' => $query
                ->join('users', 'students.user_id', '=', 'users.id')
                ->orderBy('users.full_name')
                ->select('students.*')
                ->get(),
        ], 200);
    }
}
