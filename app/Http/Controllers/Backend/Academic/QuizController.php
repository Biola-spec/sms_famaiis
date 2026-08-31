<?php

namespace App\Http\Controllers\Backend\Academic;

use App\Http\Controllers\Controller;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Passage;
use App\Models\StudentAnswer;
use App\Models\SchoolSubject;
use App\Models\StudentClass;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Quiz::with(['student_class', 'subject', 'creator'])->withCount('questions')->orderByDesc('id');

        if (!$user->hasRole('Admin') && $user->role !== 'Admin') {
            $query->where('created_by', $user->id);
        }

        $quizzes = $query->paginate(20);

        return view('backend.academic.cbt.index', compact('quizzes'));
    }

    public function destroy(Quiz $quiz)
    {
        // Security check: Only admin or the creator can delete
        $user = Auth::user();
        if ($user->role !== 'Admin' && !$user->hasRole('Admin') && $quiz->created_by !== $user->id) {
            return redirect()->back()->with([
                'message' => 'Unauthorized action.',
                'alert-type' => 'error'
            ]);
        }

        $quiz->questions()->delete();
        $quiz->attempts()->delete();
        $quiz->delete();
        
        return redirect()->back()->with([
            'message' => 'Quiz and all related data deleted successfully.',
            'alert-type' => 'success'
        ]);
    }

    public function create()
    {
        $user = Auth::user();

        if ($user->hasRole('Admin') || $user->role === 'Admin') {
            $classes = StudentClass::query()->orderBy('name')->get();
        } else {
            $assignSubjectClassIds = TeacherAssignment::query()
                ->where('teacher_id', $user->id)
                ->pluck('class_id')
                ->toArray();
                
            $assignTeacherClassIds = \App\Models\AssignClassTeacher::query()
                ->where('teacher_id', $user->id)
                ->pluck('class_id')
                ->toArray();

            $classIds = array_unique(array_merge($assignSubjectClassIds, $assignTeacherClassIds));

            $classes = StudentClass::query()
                ->whereIn('id', $classIds)
                ->orderBy('name')
                ->get();
        }

        $activeYear = getCurrentSession() ?? \App\Models\StudentYear::where('is_active', 1)->first() ?? \App\Models\StudentYear::first();
        $terms = ['1st Term', '2nd Term', '3rd Term'];

        return view('backend.academic.cbt.create', compact('classes', 'terms', 'activeYear'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:student_classes,id',
            'section_id' => 'nullable|exists:school_sections,id',
            'student_id' => 'nullable|exists:users,id',
            'subject_id' => 'required|exists:school_subjects,id',
            'term' => ['required', \Illuminate\Validation\Rule::in(['1st Term', '2nd Term', '3rd Term'])],
            'title' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'retake_limit' => 'required|integer|min:1',
            'exam_date' => 'nullable|date',
            'exam_time' => 'nullable',
            'exam_end_date' => 'nullable|date',
            'exam_end_time' => 'nullable',
        ]);

        if (!$this->canUseClass((int) $request->class_id)) {
            return redirect()->back()->with([
                'message' => 'You can only create CBT exams for your assigned classes.',
                'alert-type' => 'error',
            ]);
        }

        $startsAt = $this->resolveStartsAt($request);
        $duration = (int) $request->duration;

        if ($startsAt && $request->filled('exam_end_date')) {
            $endTimeStr = $request->filled('exam_end_time') ? $request->exam_end_time : '00:00';
            $endsAtCalc = \Carbon\Carbon::parse($request->exam_end_date . ' ' . $endTimeStr);
            if ($endsAtCalc->gt($startsAt)) {
                $duration = $startsAt->diffInMinutes($endsAtCalc);
            }
        }

        if ($overlap = $this->overlapRedirect((int) $request->class_id, $startsAt, $duration)) {
            return $overlap;
        }

        $quiz = Quiz::create([
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'student_id' => $request->student_id,
            'subject_id' => $request->subject_id,
            'term' => $request->term,
            'title' => $request->title,
            'duration' => $duration,
            'starts_at' => $startsAt,
            'retake_limit' => $request->retake_limit,
            'created_by' => Auth::id(),
            'status' => 'locked', // start locked until questions are added
        ]);

        return redirect()->route('academic.cbt.show', $quiz->id)->with([
            'message' => 'Quiz created successfully. Now add questions.',
            'alert-type' => 'success'
        ]);
    }

    public function edit(Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);

        $user = Auth::user();
        if ($user->hasRole('Admin') || $user->role === 'Admin') {
            $classes = StudentClass::query()->orderBy('name')->get();
        } else {
            $assignSubjectClassIds = TeacherAssignment::query()
                ->where('teacher_id', $user->id)
                ->pluck('class_id')
                ->toArray();
                
            $assignTeacherClassIds = \App\Models\AssignClassTeacher::query()
                ->where('teacher_id', $user->id)
                ->pluck('class_id')
                ->toArray();

            $classIds = array_unique(array_merge($assignSubjectClassIds, $assignTeacherClassIds));

            $classes = StudentClass::query()
                ->whereIn('id', $classIds)
                ->orderBy('name')
                ->get();
        }

        $activeYear = getCurrentSession() ?? \App\Models\StudentYear::where('is_active', 1)->first() ?? \App\Models\StudentYear::first();
        $terms = ['1st Term', '2nd Term', '3rd Term'];

        return view('backend.academic.cbt.edit', compact('quiz', 'classes', 'terms', 'activeYear'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);

        $request->validate([
            'class_id' => 'required|exists:student_classes,id',
            'section_id' => 'nullable|exists:school_sections,id',
            'student_id' => 'nullable|exists:users,id',
            'subject_id' => 'required|exists:school_subjects,id',
            'term' => ['required', \Illuminate\Validation\Rule::in(['1st Term', '2nd Term', '3rd Term'])],
            'title' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'retake_limit' => 'required|integer|min:1',
            'exam_date' => 'nullable|date',
            'exam_time' => 'nullable',
            'exam_end_date' => 'nullable|date',
            'exam_end_time' => 'nullable',
        ]);

        if (!$this->canUseClass((int) $request->class_id)) {
            return redirect()->back()->with([
                'message' => 'You can only assign CBT exams to your assigned classes.',
                'alert-type' => 'error',
            ]);
        }

        $startsAt = $this->resolveStartsAt($request);
        $duration = (int) $request->duration;

        if ($startsAt && $request->filled('exam_end_date')) {
            $endTimeStr = $request->filled('exam_end_time') ? $request->exam_end_time : '00:00';
            $endsAtCalc = \Carbon\Carbon::parse($request->exam_end_date . ' ' . $endTimeStr);
            if ($endsAtCalc->gt($startsAt)) {
                $duration = $startsAt->diffInMinutes($endsAtCalc);
            }
        }

        if ($overlap = $this->overlapRedirect((int) $request->class_id, $startsAt, $duration, (int) $quiz->id)) {
            return $overlap;
        }

        $quiz->update([
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'student_id' => $request->student_id,
            'subject_id' => $request->subject_id,
            'term' => $request->term,
            'title' => $request->title,
            'duration' => $duration,
            'starts_at' => $startsAt,
            'retake_limit' => $request->retake_limit,
        ]);

        return redirect()->route('academic.cbt.index')->with([
            'message' => 'Quiz setup updated successfully.',
            'alert-type' => 'success'
        ]);
    }

    public function show(Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);

        $quiz->load(['student_class', 'subject', 'questions', 'attempts.student', 'retakes.student', 'retakes.teacher']);
        
        return view('backend.academic.cbt.show', compact('quiz'));
    }

    public function addQuestion(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);

        $request->validate([
            'question' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'option_e' => 'nullable|string',
            'correct_answer' => 'required|in:A,B,C,D,E',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'image_a' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'image_b' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'image_c' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'image_d' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'image_e' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $data = $request->only([
            'question', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'correct_answer'
        ]);

        $imageFields = ['image', 'image_a', 'image_b', 'image_c', 'image_d', 'image_e'];
        foreach ($imageFields as $field) {
            if ($request->file($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . $field . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upload/questions'), $filename);
                $data[$field] = $filename;
            }
        }

        $quiz->questions()->create($data);

        return redirect()->back()->with([
            'message' => 'Question added successfully.',
            'alert-type' => 'success'
        ]);
    }

    public function addPassage(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);

        $request->validate([
            'content' => 'required|string',
            'start_number' => 'required|integer',
            'end_number' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $data = $request->only(['content', 'start_number', 'end_number']);
        $data['quiz_id'] = $quiz->id;

        if ($request->file('image')) {
            $file = $request->file('image');
            $filename = time() . '_passage_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('upload/questions'), $filename);
            $data['image'] = $filename;
        }

        Passage::create($data);

        return redirect()->back()->with([
            'message' => 'Passage added successfully.',
            'alert-type' => 'success'
        ]);
    }

    public function deletePassage(Passage $passage)
    {
        $this->authorizeQuiz($passage->quiz);

        $passage->delete();
        return redirect()->back()->with([
            'message' => 'Passage deleted successfully.',
            'alert-type' => 'success'
        ]);
    }

    public function editQuestion(Question $question)
    {
        $quiz = $question->quiz;
        $this->authorizeQuiz($quiz);

        return view('backend.academic.cbt.edit_question', compact('question', 'quiz'));
    }

    public function updateQuestion(Request $request, Question $question)
    {
        $this->authorizeQuiz($question->quiz);

        $request->validate([
            'question' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'option_e' => 'nullable|string',
            'correct_answer' => 'required|in:A,B,C,D,E',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'image_a' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'image_b' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'image_c' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'image_d' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'image_e' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $data = $request->only([
            'question', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'correct_answer'
        ]);

        $imageFields = ['image', 'image_a', 'image_b', 'image_c', 'image_d', 'image_e'];
        foreach ($imageFields as $field) {
            if ($request->file($field)) {
                if ($question->$field && file_exists(public_path('upload/questions/' . $question->$field))) {
                    @unlink(public_path('upload/questions/' . $question->$field));
                }
                
                $file = $request->file($field);
                $filename = time() . '_' . $field . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('upload/questions'), $filename);
                $data[$field] = $filename;
            }
        }

        $question->update($data);

        return redirect()->route('academic.cbt.show', $question->quiz_id)->with([
            'message' => 'Question updated successfully.',
            'alert-type' => 'success'
        ]);
    }

    public function editPassage(Passage $passage)
    {
        $quiz = $passage->quiz;
        $this->authorizeQuiz($quiz);

        return view('backend.academic.cbt.edit_passage', compact('passage', 'quiz'));
    }

    public function updatePassage(Request $request, Passage $passage)
    {
        $this->authorizeQuiz($passage->quiz);

        $request->validate([
            'content' => 'required|string',
            'start_number' => 'required|integer',
            'end_number' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $data = $request->only(['content', 'start_number', 'end_number']);

        if ($request->file('image')) {
            if ($passage->image && file_exists(public_path('upload/questions/' . $passage->image))) {
                @unlink(public_path('upload/questions/' . $passage->image));
            }

            $file = $request->file('image');
            $filename = time() . '_passage_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('upload/questions'), $filename);
            $data['image'] = $filename;
        }

        $passage->update($data);

        return redirect()->route('academic.cbt.show', $passage->quiz_id)->with([
            'message' => 'Passage updated successfully.',
            'alert-type' => 'success'
        ]);
    }

    public function allowRetake(QuizAttempt $attempt)
    {
        $this->authorizeQuiz($attempt->quiz);

        // Log the retake action
        \App\Models\QuizRetake::create([
            'quiz_id' => $attempt->quiz_id,
            'student_id' => $attempt->student_id,
            'teacher_id' => Auth::id(),
            'granted_at' => now(),
        ]);

        // Delete answers first
        StudentAnswer::where('attempt_id', $attempt->id)->delete();
        
        // Delete the attempt
        $attempt->delete();

        return redirect()->back()->with([
            'message' => 'Retake granted successfully and logged.',
            'alert-type' => 'success'
        ]);
    }

    public function import(Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);

        return view('backend.academic.cbt.import', compact('quiz'));
    }

    public function processImport(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);

        $request->validate([
            'word_file' => 'required|mimes:docx|max:10240',
        ]);

        try {
            $file = $request->file('word_file');
            $tempPath = storage_path('app/temp_quiz_' . time() . '.docx');
            copy($file->getPathname(), $tempPath);

            // Sanitize XML to remove problematic Math tags that cause library crashes
            $this->sanitizeDocx($tempPath);

            // Prevent namespace errors from crashing the load
            libxml_use_internal_errors(true);
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempPath);
            libxml_clear_errors();

            // Clean up temp file
            if (file_exists($tempPath)) unlink($tempPath);

            $allText = '';
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $itemText = '';
                    $isBold = false;
                    $isColored = false;
                    
                    $this->parseElement($element, $itemText, $isBold, $isColored);
                    
                    // If this specific element was bold/colored and looks like an option, tag it
                    if (($isBold || $isColored) && preg_match('/^[A-E][\.\)]/i', trim($itemText))) {
                        $itemText = '[CORRECT]' . $itemText;
                    }
                    
                    $allText .= $itemText . "\n";
                }
            }

            $questions = [];
            $currentQuestion = null;
            $lines = explode("\n", $allText);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Split line if it contains multiple segments (e.g. Q1. ... A. ... B. ...)
                $segments = preg_split('/(?=Q?\d+[\.\)]|\d+[\.\)]|[A-E][\.\)])/i', $line, -1, PREG_SPLIT_NO_EMPTY);
                
                foreach ($segments as $segment) {
                    $segment = trim($segment);
                    if (empty($segment)) continue;

                    // Detect Question
                    if (preg_match('/^(?:Q)?(\d+)[\.\)]\s*(.*)$/i', $segment, $matches)) {
                        if ($currentQuestion) $questions[] = $currentQuestion;
                        $currentQuestion = [
                            'quiz_id' => $quiz->id,
                            'question' => $matches[2],
                            'option_a' => '', 'option_b' => '', 'option_c' => '', 'option_d' => '', 'option_e' => '',
                            'correct_answer' => 'A',
                        ];
                    }
                    // Detect Option
                    elseif (preg_match('/^(\[CORRECT\])?([A-E])[\.\)]\s*(.*)$/i', $segment, $matches)) {
                        if ($currentQuestion) {
                            $isCorrect = !empty($matches[1]);
                            $letter = strtoupper($matches[2]);
                            $optionKey = 'option_' . strtolower($letter);
                            $currentQuestion[$optionKey] = $matches[3];
                            if ($isCorrect) $currentQuestion['correct_answer'] = $letter;
                        }
                    }
                    // Append to current question text
                    elseif ($currentQuestion && empty($currentQuestion['option_a'])) {
                        $currentQuestion['question'] .= ' ' . $segment;
                    }
                }
            }

            if ($currentQuestion) $questions[] = $currentQuestion;

            if (empty($questions)) {
                return redirect()->back()->with([
                    'message' => 'No questions found. Please ensure they follow the format: 1. Question, A. Option',
                    'alert-type' => 'error'
                ]);
            }

            foreach ($questions as $qData) {
                \App\Models\Question::create($qData);
            }

            return redirect()->route('academic.cbt.show', $quiz->id)->with([
                'message' => count($questions) . ' questions imported successfully.',
                'alert-type' => 'success'
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => 'Error processing file: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }

    private function sanitizeDocx($path)
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) === true) {
            $xmlContent = $zip->getFromName('word/document.xml');
            if ($xmlContent) {
                // Instead of just removing, extract the text from the math elements so math content is preserved as text
                $xmlContent = preg_replace_callback('/<m:oMath>(.*?)<\/m:oMath>/s', function($matches) {
                    preg_match_all('/<m:t>(.*?)<\/m:t>/s', $matches[1], $tMatches);
                    return implode('', $tMatches[1]);
                }, $xmlContent);
                
                // Also handle Math Paragraphs
                $xmlContent = preg_replace_callback('/<m:oMathPara>(.*?)<\/m:oMathPara>/s', function($matches) {
                    preg_match_all('/<m:t>(.*?)<\/m:t>/s', $matches[1], $tMatches);
                    return implode('', $tMatches[1]);
                }, $xmlContent);

                $zip->addFromString('word/document.xml', $xmlContent);
            }
            $zip->close();
        }
    }

    private function parseElement($element, &$text, &$isBold, &$isColored)
    {
        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            foreach ($element->getElements() as $child) {
                if ($child instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text .= $child->getText();
                    if ($child->getFontStyle()->isBold()) $isBold = true;
                    if ($child->getFontStyle()->getColor() && !in_array($child->getFontStyle()->getColor(), ['000000', 'auto'])) $isColored = true;
                } elseif ($child instanceof \PhpOffice\PhpWord\Element\Image) {
                    $text .= $this->saveImage($child);
                } elseif (method_exists($child, 'getText')) {
                    $text .= $child->getText();
                }
            }
        } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $text = $element->getText();
            if ($element->getFontStyle()->isBold()) $isBold = true;
        } elseif ($element instanceof \PhpOffice\PhpWord\Element\Image) {
            $text .= $this->saveImage($element);
        }
    }

    private function saveImage($element)
    {
        try {
            $imageName = time() . '_' . uniqid() . '.' . $element->getExtension();
            $path = public_path('upload/questions/' . $imageName);
            if (!file_exists(public_path('upload/questions'))) {
                mkdir(public_path('upload/questions'), 0777, true);
            }
            file_put_contents($path, $element->getImageBlob());
            return '<img src="' . asset('upload/questions/' . $imageName) . '" style="max-width:100%;">';
        } catch (\Exception $e) {
            return '';
        }
    }

    public function deleteQuestion(Question $question)
    {
        $this->authorizeQuiz($question->quiz);

        $question->delete();
        return redirect()->back()->with([
            'message' => 'Question deleted successfully.',
            'alert-type' => 'success'
        ]);
    }

    public function updateStatus(Request $request, Quiz $quiz)
    {
        $this->authorizeQuiz($quiz);

        $request->validate(['status' => 'required|in:published,locked']);
        
        if ($request->status === 'published' && $quiz->questions()->count() === 0) {
            return redirect()->back()->with([
                'message' => 'Cannot publish a quiz with no questions.',
                'alert-type' => 'error'
            ]);
        }

        $quiz->update(['status' => $request->status]);

        return redirect()->back()->with([
            'message' => 'Quiz status updated to ' . $request->status,
            'alert-type' => 'success'
        ]);
    }

    private function resolveStartsAt(Request $request)
    {
        if (!$request->filled('exam_date')) {
            return null;
        }

        $time = $request->filled('exam_time') ? $request->exam_time : '00:00';

        return \Carbon\Carbon::parse($request->exam_date . ' ' . $time);
    }

    private function overlapRedirect(int $classId, $startsAt, int $duration, ?int $ignoreId = null)
    {
        if (!$startsAt) {
            return null;
        }

        $endsAt = $startsAt->copy()->addMinutes($duration);

        if (Quiz::hasScheduleOverlap($classId, $startsAt, $endsAt, $ignoreId)) {
            return redirect()->back()->withInput()->with([
                'message' => 'This class already has a CBT exam scheduled that overlaps this time.',
                'alert-type' => 'error',
            ]);
        }

        return null;
    }

    private function authorizeQuiz(Quiz $quiz): void
    {
        $user = Auth::user();

        if ($user->hasRole('Admin') || $user->role === 'Admin') {
            return;
        }

        abort_unless((int) $quiz->created_by === (int) $user->id && $this->canUseClass((int) $quiz->class_id), 403);
    }

    private function canUseClass(int $classId): bool
    {
        $user = Auth::user();

        if ($user->hasRole('Admin') || $user->role === 'Admin') {
            return true;
        }

        return TeacherAssignment::where('teacher_id', $user->id)->where('class_id', $classId)->exists()
            || \App\Models\AssignClassTeacher::where('teacher_id', $user->id)->where('class_id', $classId)->exists()
            || \App\Models\AssignSubject::where('teacher_id', $user->id)->where('class_id', $classId)->exists();
    }
}
