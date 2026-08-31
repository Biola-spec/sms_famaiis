<?php

namespace App\Http\Controllers\Backend\Academic;

use App\Http\Controllers\Controller;
use App\Models\AssignClassTeacher;
use App\Models\AssignStudent;
use App\Models\ClassGalleryPhoto;
use App\Models\StudentClass;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassGalleryController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($this->isStudent($user)) {
            $classId = $this->studentClassId($user);
            if (!$classId) {
                return redirect()->back()->with([
                    'message' => 'You are not assigned to a class for the current session.',
                    'alert-type' => 'error',
                ]);
            }

            return redirect()->route('class.gallery.show', $classId);
        }

        if ($this->isAdmin($user)) {
            $classes = StudentClass::query()->orderBy('name')->get();
        } else {
            $classes = StudentClass::query()
                ->whereIn('id', $this->teacherClassIds($user))
                ->orderBy('name')
                ->get();
        }

        return view('backend.academic.gallery.index', compact('classes'));
    }

    public function show(StudentClass $class)
    {
        $this->authorizeView($class);

        $session = getCurrentSession();
        $assignments = AssignStudent::with('student')
            ->where('class_id', $class->id)
            ->whereHas('student')
            ->when($session, function ($query) use ($session) {
                $query->where('year_id', $session->id);
            })
            ->get();

        $photos = ClassGalleryPhoto::where('class_id', $class->id)
            ->get()
            ->keyBy('student_id');

        $canManage = $this->isAdmin(Auth::user());

        return view('backend.academic.gallery.show', compact('class', 'assignments', 'photos', 'canManage'));
    }

    public function store(Request $request, StudentClass $class)
    {
        abort_unless($this->isAdmin(Auth::user()), 403);

        $request->validate([
            'student_id' => 'required|exists:users,id',
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $session = getCurrentSession();
        $inClass = AssignStudent::where('class_id', $class->id)
            ->where('student_id', $request->student_id)
            ->when($session, function ($query) use ($session) {
                $query->where('year_id', $session->id);
            })
            ->exists();

        abort_unless($inClass, 422, 'Student is not in this class.');

        $file = $request->file('photo');
        $filename = date('YmdHis') . '_' . $request->student_id . '.' . $file->getClientOriginalExtension();
        $dir = public_path('upload/class_gallery');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file->move($dir, $filename);
        $path = 'upload/class_gallery/' . $filename;

        $existing = ClassGalleryPhoto::where('class_id', $class->id)
            ->where('student_id', $request->student_id)
            ->first();

        if ($existing) {
            $old = public_path($existing->photo_path);
            if (is_file($old)) {
                @unlink($old);
            }
            $existing->update([
                'photo_path' => $path,
                'uploaded_by' => Auth::id(),
            ]);
        } else {
            ClassGalleryPhoto::create([
                'class_id' => $class->id,
                'student_id' => $request->student_id,
                'photo_path' => $path,
                'uploaded_by' => Auth::id(),
            ]);
        }

        return redirect()->back()->with([
            'message' => 'Class photo saved.',
            'alert-type' => 'success',
        ]);
    }

    public function destroy(ClassGalleryPhoto $photo)
    {
        abort_unless($this->isAdmin(Auth::user()), 403);

        $old = public_path($photo->photo_path);
        if (is_file($old)) {
            @unlink($old);
        }
        $photo->delete();

        return redirect()->back()->with([
            'message' => 'Photo removed.',
            'alert-type' => 'success',
        ]);
    }

    private function authorizeView(StudentClass $class): void
    {
        $user = Auth::user();

        if ($this->isAdmin($user)) {
            return;
        }

        if ($this->isStudent($user)) {
            abort_unless((int) $this->studentClassId($user) === (int) $class->id, 403);
            return;
        }

        abort_unless(in_array((int) $class->id, $this->teacherClassIds($user), true), 403);
    }

    private function studentClassId($user): ?int
    {
        $session = getCurrentSession();
        $assign = AssignStudent::where('student_id', $user->id)
            ->when($session, function ($query) use ($session) {
                $query->where('year_id', $session->id);
            })
            ->first();

        return $assign ? (int) $assign->class_id : null;
    }

    private function teacherClassIds($user): array
    {
        $assignSubject = TeacherAssignment::where('teacher_id', $user->id)->pluck('class_id')->all();
        $classTeacher = AssignClassTeacher::where('teacher_id', $user->id)->pluck('class_id')->all();

        return array_map('intval', array_unique(array_merge($assignSubject, $classTeacher)));
    }

    private function isAdmin($user): bool
    {
        return $user && ($user->role === 'Admin' || $user->hasRole('Admin'));
    }

    private function isStudent($user): bool
    {
        return $user && ($user->role === 'Student' || $user->hasRole('Student'));
    }
}
