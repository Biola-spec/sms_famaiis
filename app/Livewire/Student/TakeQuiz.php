<?php

namespace App\Livewire\Student;

use App\Models\MarksGrade;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use App\Models\StudentMarks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TakeQuiz extends Component
{
    public int $quizId;
    public string $quizTitle = '';
    public array $questions = [];
    public array $passages = [];
    public array $answers = [];
    public int $timeLeft = 0;
    public int $attemptId = 0;
    public int $currentQuestionIndex = 0;
    public bool $submitting = false;

    public function mount(Quiz $quiz)
    {
        if (!$quiz->isAvailableToTake()) {
            return redirect()->route('student.cbt.index')->with([
                'message' => 'This quiz is locked until ' . optional($quiz->starts_at)->format('M d, Y h:i A') . '.',
                'alert-type' => 'error',
            ]);
        }

        $this->quizId = $quiz->id;
        $this->quizTitle = $quiz->title;

        $orderedQuestions = $quiz->questions()->orderBy('id')->get()->values();
        $this->questions = $orderedQuestions->map(function (Question $question) {
            return [
                'id' => (int) $question->id,
                'question' => (string) $question->question,
                'image' => $question->image,
                'option_a' => $question->option_a,
                'option_b' => $question->option_b,
                'option_c' => $question->option_c,
                'option_d' => $question->option_d,
                'option_e' => $question->option_e,
                'image_a' => $question->image_a,
                'image_b' => $question->image_b,
                'image_c' => $question->image_c,
                'image_d' => $question->image_d,
                'image_e' => $question->image_e,
            ];
        })->all();

        $this->passages = $quiz->passages()->orderBy('start_number')->get()->map(function ($passage) {
            return [
                'id' => (int) $passage->id,
                'content' => (string) $passage->content,
                'image' => $passage->image,
                'start_number' => (int) $passage->start_number,
                'end_number' => (int) $passage->end_number,
            ];
        })->all();

        $attempt = QuizAttempt::where('student_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->where('status', 'in-progress')
            ->first();

        if (!$attempt) {
            $completedCount = QuizAttempt::where('student_id', Auth::id())
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->count();

            if ($completedCount >= $quiz->retake_limit) {
                return redirect()->route('student.cbt.index');
            }

            $attempt = QuizAttempt::create([
                'student_id' => Auth::id(),
                'quiz_id' => $quiz->id,
                'status' => 'in-progress',
            ]);
        }

        $this->attemptId = $attempt->id;

        foreach ($this->questions as $question) {
            $this->answers[(string) $question['id']] = null;
        }

        $startTime = $attempt->created_at->timestamp;
        $durationSeconds = max(1, (int) $quiz->duration) * 60;
        $elapsed = time() - $startTime;
        $this->timeLeft = max(0, $durationSeconds - $elapsed);

        if ($this->timeLeft <= 0 && count($this->questions) > 0) {
            return $this->submit();
        }
    }

    public function selectOption($questionId, $option): void
    {
        $questionId = (string) $questionId;
        if (!array_key_exists($questionId, $this->answers)) {
            return;
        }

        $option = strtoupper((string) $option);
        if (!in_array($option, ['A', 'B', 'C', 'D', 'E'], true)) {
            return;
        }

        $this->answers[$questionId] = $option;
    }

    public function nextQuestion(): void
    {
        if ($this->currentQuestionIndex < count($this->questions) - 1) {
            $this->currentQuestionIndex++;
            $this->dispatch('cbt-question-changed');
        }
    }

    public function previousQuestion(): void
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
            $this->dispatch('cbt-question-changed');
        }
    }

    public function goToQuestion($index): void
    {
        $index = (int) $index;
        if ($index >= 0 && $index < count($this->questions)) {
            $this->currentQuestionIndex = $index;
            $this->dispatch('cbt-question-changed');
        }
    }

    public function submit()
    {
        if ($this->submitting) {
            return null;
        }

        $this->submitting = true;

        $attempt = QuizAttempt::where('id', $this->attemptId)
            ->where('student_id', Auth::id())
            ->first();

        if (!$attempt || $attempt->status === 'completed') {
            return redirect()->route('student.cbt.index');
        }

        $dbQuestions = Question::where('quiz_id', $this->quizId)->orderBy('id')->get();
        $score = 0;
        $answersData = [];

        foreach ($dbQuestions as $question) {
            $selected = $this->answers[(string) $question->id] ?? $this->answers[$question->id] ?? null;
            $isCorrect = $selected !== null && strtoupper((string) $selected) === strtoupper((string) $question->correct_answer);

            if ($isCorrect) {
                $score++;
            }

            $answersData[] = [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_option' => $selected,
                'is_correct' => $isCorrect,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        StudentAnswer::where('attempt_id', $attempt->id)->delete();
        if (!empty($answersData)) {
            StudentAnswer::insert($answersData);
        }

        $attempt->update([
            'score' => $score,
            'status' => 'completed',
            'submitted_at' => now(),
        ]);

        $this->syncMarks($score, $dbQuestions->count());

        session()->flash('message', 'Quiz Submitted Successfully!');
        session()->flash('alert-type', 'success');

        return redirect()->route('student.cbt.result', $attempt->id);
    }

    private function syncMarks($score, $totalQuestions)
    {
        $quiz = Quiz::find($this->quizId);
        if (!$quiz) {
            return;
        }

        $totalQuestions = $totalQuestions > 0 ? $totalQuestions : 1;
        $percentage = ($score / $totalQuestions) * 100;
        $session = getCurrentSession();

        $grade = MarksGrade::where('start_marks', '<=', $percentage)
            ->where('end_marks', '>=', $percentage)
            ->first();

        StudentMarks::updateOrCreate(
            [
                'student_id' => Auth::id(),
                'subject_id' => $quiz->subject_id,
                'class_id' => $quiz->class_id,
                'session_id' => optional($session)->id,
                'term' => $quiz->term,
            ],
            [
                'year_id' => optional($session)->id,
                'id_no' => Auth::user()->id_no,
                'total_score' => $percentage,
                'marks' => $percentage,
                'exam_score' => $percentage,
                'grade' => $grade ? $grade->grade_name : null,
            ]
        );
    }

    public function render()
    {
        $question = $this->questions[$this->currentQuestionIndex] ?? null;
        $questionNumber = $this->currentQuestionIndex + 1;
        $activePassages = [];

        if ($question) {
            foreach ($this->passages as $passage) {
                if ($questionNumber >= $passage['start_number'] && $questionNumber <= $passage['end_number']) {
                    $activePassages[] = $passage;
                }
            }
        }

        $answeredCount = collect($this->answers)->filter(fn ($value) => $value !== null && $value !== '')->count();

        return view('livewire.student.take-quiz', [
            'currentQuestion' => $question,
            'questionNumber' => $questionNumber,
            'totalQuestions' => count($this->questions),
            'activePassages' => $activePassages,
            'answeredCount' => $answeredCount,
        ]);
    }
}
