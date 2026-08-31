<?php

namespace App\Livewire\Student;

use App\Models\MarksGrade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentMarks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TakeQuiz extends Component
{
    public Quiz $quiz;
    public $questions;
    public $answers = [];
    public $timeLeft;
    public $attempt;
    public $currentQuestionIndex = 0;

    public function mount(Quiz $quiz)
    {
        if (!$quiz->isAvailableToTake()) {
            return redirect()->route('student.cbt.index')->with([
                'message' => 'This quiz is locked until ' . optional($quiz->starts_at)->format('M d, Y h:i A') . '.',
                'alert-type' => 'error',
            ]);
        }

        $this->quiz = $quiz->load(['questions', 'passages']);
        $this->questions = $this->quiz->questions;
        
        $this->attempt = QuizAttempt::where('student_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->where('status', 'in-progress')
            ->first();

        if (!$this->attempt) {
            $completedCount = QuizAttempt::where('student_id', Auth::id())
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->count();

            if ($completedCount >= $quiz->retake_limit) {
                return redirect()->route('student.cbt.index');
            }

            $this->attempt = QuizAttempt::create([
                'student_id' => Auth::id(),
                'quiz_id' => $quiz->id,
                'status' => 'in-progress'
            ]);
        }

        foreach ($this->questions as $q) {
            $this->answers[$q->id] = null;
        }

        $startTime = $this->attempt->created_at->timestamp;
        $durationSeconds = $this->quiz->duration * 60;
        $elapsed = time() - $startTime;
        $this->timeLeft = max(0, $durationSeconds - $elapsed);
    }

    public function submit()
    {
        if (!$this->attempt || $this->attempt->status === 'completed') {
            return redirect()->route('student.cbt.index');
        }

        $score = 0;
        $answersData = [];
        
        foreach ($this->questions as $question) {
            $selected = $this->answers[$question->id] ?? null;
            $isCorrect = ($selected === $question->correct_answer);
            
            if ($isCorrect) {
                $score++;
            }

            $answersData[] = [
                'attempt_id' => $this->attempt->id,
                'question_id' => $question->id,
                'selected_option' => $selected,
                'is_correct' => $isCorrect,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert answers for performance
        \App\Models\StudentAnswer::insert($answersData);

        $this->attempt->update([
            'score' => $score,
            'status' => 'completed',
            'submitted_at' => now(),
        ]);

        $this->syncMarks($score);

        session()->flash('message', 'Quiz Submitted Successfully!');
        session()->flash('alert-type', 'success');

        return redirect()->route('student.cbt.result', $this->attempt->id);
    }

    public function nextQuestion()
    {
        if ($this->currentQuestionIndex < count($this->questions) - 1) {
            $this->currentQuestionIndex++;
        }
    }

    public function previousQuestion()
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }

    public function goToQuestion($index)
    {
        if ($index >= 0 && $index < count($this->questions)) {
            $this->currentQuestionIndex = $index;
        }
    }

    private function syncMarks($score)
    {
        $totalQuestions = $this->questions->count() > 0 ? $this->questions->count() : 1;
        $percentage = ($score / $totalQuestions) * 100;
        $session = getCurrentSession();

        $grade = MarksGrade::where('start_marks', '<=', $percentage)
            ->where('end_marks', '>=', $percentage)
            ->first();

        StudentMarks::updateOrCreate(
            [
                'student_id' => Auth::id(),
                'subject_id' => $this->quiz->subject_id,
                'class_id' => $this->quiz->class_id,
                'session_id' => optional($session)->id,
                'term' => $this->quiz->term,
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
        return view('livewire.student.take-quiz');
    }
}
