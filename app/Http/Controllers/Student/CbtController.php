<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssignStudent;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CbtController extends Controller
{
    public function index()
    {
        $session = getCurrentSession();
        $user = Auth::user();

        $assign = AssignStudent::where('student_id', $user->id)
            ->where('year_id', optional($session)->id)
            ->first();
        
        if (!$assign) {
            $quizzes = collect();
            $attempts = collect();
            return view('student.cbt.index', compact('quizzes', 'attempts'));
        }

        $section = \App\Models\StudentSection::where('student_id', $user->id)
            ->where('year_id', optional($session)->id)
            ->first();
        $sectionId = $section ? $section->section_id : null;

        $settings = \App\Models\AcademicSetting::first();
        $currentTerm = $settings ? $settings->current_term : null;

        $quizzes = Quiz::where('class_id', $assign->class_id)
            ->where('status', 'published')
            ->where(function($q) use ($user, $sectionId) {
                // If specific student
                $q->where('student_id', $user->id)
                  // OR specific section
                  ->orWhere(function($sq) use ($sectionId) {
                      $sq->whereNull('student_id')->where('section_id', $sectionId);
                  })
                  // OR whole class
                  ->orWhere(function($sq) {
                      $sq->whereNull('student_id')->whereNull('section_id');
                  });
            });

        if ($currentTerm) {
            $quizzes->where('term', $currentTerm);
        }

        $quizzes = $quizzes->with(['subject'])->get();
            
        $attempts = QuizAttempt::where('student_id', $user->id)->get()->groupBy('quiz_id');

        return view('student.cbt.index', compact('quizzes', 'attempts'));
    }

    public function take(Quiz $quiz)
    {
        $session = getCurrentSession();
        $assign = AssignStudent::where('student_id', Auth::id())
            ->where('year_id', optional($session)->id)
            ->first();

        if (!$assign || $quiz->class_id !== $assign->class_id || $quiz->status !== 'published') {
            return redirect()->route('student.cbt.index')->with([
                'message' => 'You are not authorized to take this quiz.',
                'alert-type' => 'error'
            ]);
        }

        if (!$quiz->isAvailableToTake()) {
            return redirect()->route('student.cbt.index')->with([
                'message' => 'This quiz is locked until ' . optional($quiz->starts_at)->format('M d, Y h:i A') . '.',
                'alert-type' => 'error'
            ]);
        }

        $attempts = QuizAttempt::where('student_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->count();

        if ($attempts >= $quiz->retake_limit) {
            return redirect()->route('student.cbt.index')->with([
                'message' => 'You have reached the maximum number of attempts for this quiz.',
                'alert-type' => 'error'
            ]);
        }

        return view('student.cbt.take', compact('quiz'));
    }

    public function result(QuizAttempt $attempt)
    {
        if ($attempt->student_id !== Auth::id()) {
            abort(403);
        }

        $attempt->load(['quiz.subject']);
        $attempt->quiz->loadCount('questions');

        $reviewUnlocked = $attempt->quiz->reviewIsUnlocked();
        if ($reviewUnlocked) {
            $attempt->load('answers.question');
        }

        return view('student.cbt.result', compact('attempt', 'reviewUnlocked'));
    }

    public function download(QuizAttempt $attempt)
    {
        if ($attempt->student_id !== Auth::id()) {
            abort(403);
        }

        $attempt->load(['quiz.subject']);

        if (!$attempt->quiz->reviewIsUnlocked()) {
            return redirect()->route('student.cbt.result', $attempt->id)->with([
                'message' => 'Full review is available after ' . optional($attempt->quiz->examEndTime())->format('M d, Y h:i A') . '.',
                'alert-type' => 'error',
            ]);
        }

        $attempt->load('answers.question');
        $attempt->quiz->loadCount('questions');
        $reviewUnlocked = true;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('student.cbt.result_pdf', compact('attempt', 'reviewUnlocked'));

        return $pdf->download('cbt-result-' . $attempt->id . '.pdf');
    }
}
