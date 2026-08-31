<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssignStudent;
use App\Models\User;
use App\Models\DiscountStudent;

use App\Models\StudentYear;
use App\Models\StudentClass;
use App\Models\StudentGroup;
use App\Models\StudentShift;
use DB;
use PDF;

use App\Models\AssignSubject;
use App\Models\TeacherAssignment;
use App\Models\StudentMarks;
use App\Models\ExamType;
use App\Models\StudentSection;
use Auth;

class DefaultController extends Controller
{
    public function GetSubject(Request $request)
    {
        $class_id = $request->class_id;
        $user = Auth::user();
        
        $query = AssignSubject::with(['school_subject'])->where('class_id', $class_id);

        if ($request->filled('section_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('section_id', $request->section_id)->orWhereNull('section_id');
            });
        }
        
        if ($user && $user->role !== 'Admin' && !$user->hasRole('Admin')) {
            $query->whereIn('subject_id', function ($teacherSubjectQuery) use ($class_id, $request, $user) {
                $teacherSubjectQuery->select('subject_id')
                    ->from('teacher_assignments')
                    ->where('teacher_id', $user->id)
                    ->where('class_id', $class_id);

                if ($request->filled('section_id')) {
                    $teacherSubjectQuery->where(function ($sectionQuery) use ($request) {
                        $sectionQuery->where('section_id', $request->section_id)
                            ->orWhereNull('section_id');
                    });
                }
            });
        }
        
        $allData = $query->get();

        $subjects = collect();
        foreach ($allData as $row) {
            $sub = $row->school_subject ?? null;
            if ($sub && !$subjects->has($sub->id)) {
                $subjects->put($sub->id, [
                    'id' => $sub->id,
                    'subject_id' => $sub->id,
                    'name' => $sub->name,
                    'school_subject' => [
                        'id' => $sub->id,
                        'name' => $sub->name,
                    ],
                ]);
            }
        }

        if ($subjects->isEmpty()) {
            if ($user && $user->role !== 'Admin' && !$user->hasRole('Admin')) {
                $teacherSubjectIds = TeacherAssignment::where('teacher_id', $user->id)
                    ->where('class_id', $class_id)
                    ->pluck('subject_id')
                    ->toArray();

                $fallbackSubjects = !empty($teacherSubjectIds)
                    ? \App\Models\SchoolSubject::whereIn('id', $teacherSubjectIds)->orderBy('name')->get()
                    : \App\Models\SchoolSubject::orderBy('name')->get();
            } else {
                $fallbackSubjects = \App\Models\SchoolSubject::orderBy('name')->get();
            }

            foreach ($fallbackSubjects as $s) {
                $subjects->put($s->id, [
                    'id' => $s->id,
                    'subject_id' => $s->id,
                    'name' => $s->name,
                    'school_subject' => [
                        'id' => $s->id,
                        'name' => $s->name,
                    ],
                ]);
            }
        }

        return response()->json($subjects->values());
    }

    public function GetStudents(Request $request)
    {
        $year_id = $request->year_id;
        $class_id = $request->class_id;
        $user = Auth::user();

        if (empty($year_id)) {
            $session = getCurrentSession();
            $year_id = $session ? $session->id : null;
        }

        $query = AssignStudent::with(['student'])->where('class_id', $class_id);

        if (!empty($year_id)) {
            $query->where('year_id', $year_id);
        }

        if ($request->filled('section_id')) {
            $studentIds = StudentSection::where('section_id', $request->section_id)->pluck('student_id');
            $query->whereIn('student_id', $studentIds);
        }

        if ($user && $user->hasRole('Parent')) {
            $childIds = $user->children->pluck('id')->toArray();
            $query->whereIn('student_id', $childIds);
        }

        $allData = $query->get();

        if ($allData->isEmpty() && !empty($year_id)) {
            // Fallback without year_id constraint if no records found for active session
            $fallbackQuery = AssignStudent::with(['student'])->where('class_id', $class_id);
            if ($request->filled('section_id')) {
                $studentIds = StudentSection::where('section_id', $request->section_id)->pluck('student_id');
                $fallbackQuery->whereIn('student_id', $studentIds);
            }
            $allData = $fallbackQuery->get();
        }

        $students = collect();
        foreach ($allData as $row) {
            $st = $row->student ?? null;
            if ($st && !$students->has($st->id)) {
                $name = $st->name;
                if (empty($name)) {
                    $name = trim(($st->fname ?? '') . ' ' . ($st->surname ?? '') . ' ' . ($st->mname ?? ''));
                }
                if (empty($name)) {
                    $name = $st->email ?? ('Student #' . $st->id);
                }
                $students->put($st->id, [
                    'id' => $st->id,
                    'student_id' => $st->id,
                    'name' => $name,
                    'id_no' => $st->id_no ?? '',
                    'student' => [
                        'id' => $st->id,
                        'name' => $name,
                        'id_no' => $st->id_no ?? '',
                    ],
                ]);
            }
        }

        return response()->json($students->values());
    }

    public function SwitchSection(Request $request)
    {
        $section_id = $request->section_id;
        if ($section_id == 'all') {
            session()->forget('active_section_id');
        } else {
            session(['active_section_id' => $section_id]);
        }
        
        $notification = [
            'message'    => 'Academic section context updated successfully',
            'alert-type' => 'success'
        ];

        return redirect()->back()->with($notification);
    }
}
 
