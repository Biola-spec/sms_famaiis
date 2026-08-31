<?php

namespace App\Http\Controllers\Backend\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLibraryResourceRequest;
use App\Models\AssignClassTeacher;
use App\Models\AssignStudent;
use App\Models\LibraryResource;
use App\Models\StudentClass;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibraryResourceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = trim((string) $request->get('q', ''));
        $query = LibraryResource::with(['teacher', 'studentClass'])->orderByDesc('id');

        if ($this->isStudent($user)) {
            $classId = $this->studentClassId($user);
            $query->where(function ($q) use ($classId) {
                $q->where('visibility', 'general');
                if ($classId) {
                    $q->orWhere(function ($inner) use ($classId) {
                        $inner->where('visibility', 'class')->where('class_id', $classId);
                    });
                }
            });
        } elseif (!$this->isAdmin($user)) {
            $query->where('teacher_id', $user->id);
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('subject', 'like', $like)
                    ->orWhere('file_type', 'like', $like);
            });
        }

        $resources = $query->paginate(12)->withQueryString();
        $canUpload = $this->isAdmin($user) || $this->isTeacher($user);

        return view('backend.academic.library.index', compact('resources', 'search', 'canUpload'));
    }

    public function create()
    {
        $this->authorizeStaff();
        $classes = $this->availableClasses();

        return view('backend.academic.library.create', compact('classes'));
    }

    public function store(StoreLibraryResourceRequest $request)
    {
        $user = Auth::user();
        $visibility = $request->visibility;
        $classId = $visibility === 'class' ? (int) $request->class_id : null;

        if ($visibility === 'class' && !$this->isAdmin($user) && !in_array($classId, $this->teacherClassIds($user), true)) {
            return redirect()->back()->withInput()->with([
                'message' => 'You can only attach resources to your assigned classes.',
                'alert-type' => 'error',
            ]);
        }

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $dir = public_path('upload/library');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file->move($dir, $filename);

        LibraryResource::create([
            'teacher_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'subject' => $request->subject,
            'file_path' => 'upload/library/' . $filename,
            'file_type' => $ext,
            'visibility' => $visibility,
            'class_id' => $classId,
        ]);

        return redirect()->route('library.index')->with([
            'message' => 'Resource uploaded.',
            'alert-type' => 'success',
        ]);
    }

    public function destroy(LibraryResource $resource)
    {
        $user = Auth::user();
        abort_unless($this->isAdmin($user) || (int) $resource->teacher_id === (int) $user->id, 403);

        $path = public_path($resource->file_path);
        if (is_file($path)) {
            @unlink($path);
        }
        $resource->delete();

        return redirect()->back()->with([
            'message' => 'Resource deleted.',
            'alert-type' => 'success',
        ]);
    }

    public function download(LibraryResource $resource)
    {
        abort_unless($this->canDownload(Auth::user(), $resource), 403);

        $path = public_path($resource->file_path);
        abort_unless(is_file($path), 404);

        $downloadName = preg_replace('/[^A-Za-z0-9_\- ]/', '', $resource->title) . '.' . $resource->file_type;

        return response()->download($path, $downloadName);
    }

    private function canDownload($user, LibraryResource $resource): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($this->isTeacher($user) && (int) $resource->teacher_id === (int) $user->id) {
            return true;
        }

        if ($this->isStudent($user)) {
            if ($resource->visibility === 'general') {
                return true;
            }

            return $resource->visibility === 'class'
                && (int) $resource->class_id === (int) $this->studentClassId($user);
        }

        return false;
    }

    private function availableClasses()
    {
        $user = Auth::user();
        if ($this->isAdmin($user)) {
            return StudentClass::query()->orderBy('name')->get();
        }

        return StudentClass::query()
            ->whereIn('id', $this->teacherClassIds($user))
            ->orderBy('name')
            ->get();
    }

    private function teacherClassIds($user): array
    {
        $assignSubject = TeacherAssignment::where('teacher_id', $user->id)->pluck('class_id')->all();
        $classTeacher = AssignClassTeacher::where('teacher_id', $user->id)->pluck('class_id')->all();

        return array_map('intval', array_unique(array_merge($assignSubject, $classTeacher)));
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

    private function authorizeStaff(): void
    {
        $user = Auth::user();
        abort_unless($this->isAdmin($user) || $this->isTeacher($user), 403);
    }

    private function isAdmin($user): bool
    {
        return $user && ($user->role === 'Admin' || $user->hasRole('Admin'));
    }

    private function isTeacher($user): bool
    {
        return $user && ($user->role === 'Teacher' || $user->hasRole('Teacher'));
    }

    private function isStudent($user): bool
    {
        return $user && ($user->role === 'Student' || $user->hasRole('Student'));
    }
}
