<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use App\Models\StudentYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CbtReviewLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Run with DB_CONNECTION=sqlite and DB_DATABASE=:memory: to avoid touching local data.');
        }

        config(['app.url' => 'http://localhost']);
        $this->createMinimalSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        foreach ([
            'student_answers', 'questions', 'quiz_attempts', 'quizzes',
            'assign_students', 'school_subjects', 'terms', 'site_settings', 'website_settings', 'events',
            'academic_settings', 'student_years', 'role_user', 'roles', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_score_is_visible_before_end_but_review_and_pdf_are_locked(): void
    {
        $this->seedSession();
        $student = User::factory()->create(['role' => 'Student']);

        $quiz = Quiz::create([
            'class_id' => 1,
            'subject_id' => 1,
            'title' => 'Timed Exam',
            'duration' => 60,
            'starts_at' => now()->subMinutes(10),
            'retake_limit' => 1,
            'created_by' => $student->id,
            'status' => 'published',
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question' => 'SECRET_QUESTION_TEXT',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        $attempt = QuizAttempt::create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'score' => 1,
            'submitted_at' => now(),
            'status' => 'completed',
        ]);

        StudentAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option' => 'A',
            'is_correct' => true,
        ]);

        $this->assertFalse($quiz->fresh()->reviewIsUnlocked());

        $result = $this->actingAs($student)->get('/student/cbt/result/' . $attempt->id);
        $result->assertOk();
        $result->assertSee('1 / 1');
        $result->assertDontSee('SECRET_QUESTION_TEXT');
        $result->assertSee('Full review available after');

        $download = $this->actingAs($student)->get('/student/cbt/result/' . $attempt->id . '/download');
        $download->assertRedirect();
        $this->assertStringNotContainsString('SECRET_QUESTION_TEXT', $download->getContent());
    }

    public function test_review_and_pdf_are_available_after_exam_end(): void
    {
        $this->seedSession();
        $student = User::factory()->create(['role' => 'Student']);

        $quiz = Quiz::create([
            'class_id' => 1,
            'subject_id' => 1,
            'title' => 'Timed Exam',
            'duration' => 10,
            'starts_at' => now()->subMinutes(30),
            'retake_limit' => 1,
            'created_by' => $student->id,
            'status' => 'published',
        ]);

        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question' => 'SECRET_QUESTION_TEXT',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        $attempt = QuizAttempt::create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'score' => 1,
            'submitted_at' => now()->subMinutes(15),
            'status' => 'completed',
        ]);

        StudentAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option' => 'B',
            'is_correct' => false,
        ]);

        Carbon::setTestNow(now()->addHour());
        $this->assertTrue($quiz->fresh()->reviewIsUnlocked());

        $result = $this->actingAs($student)->get('/student/cbt/result/' . $attempt->id);
        $result->assertOk();
        $result->assertSee('SECRET_QUESTION_TEXT');
        $result->assertSee('Correct: Option A');

        $download = $this->actingAs($student)->get('/student/cbt/result/' . $attempt->id . '/download');
        $download->assertOk();
        $download->assertHeader('content-disposition');
        $this->assertStringContainsString('application/pdf', strtolower($download->headers->get('content-type')));
    }

    public function test_student_cannot_take_quiz_before_starts_at(): void
    {
        $this->seedSession();
        $student = User::factory()->create(['role' => 'Student']);

        $year = StudentYear::query()->first();
        \App\Models\AssignStudent::create([
            'student_id' => $student->id,
            'year_id' => optional($year)->id,
            'class_id' => 1,
        ]);

        $quiz = Quiz::create([
            'class_id' => 1,
            'title' => 'Future Exam',
            'duration' => 30,
            'starts_at' => now()->addHour(),
            'retake_limit' => 1,
            'created_by' => $student->id,
            'status' => 'published',
        ]);

        $this->assertFalse($quiz->fresh()->isAvailableToTake());

        $response = $this->actingAs($student)->get('/student/cbt/' . $quiz->id . '/take');
        $response->assertRedirect('/student/cbt');
    }

    private function seedSession(): void
    {
        $year = StudentYear::create(['name' => '2026/2027', 'is_active' => true]);
        \DB::table('academic_settings')->insert([
            'current_session_id' => $year->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('site_settings')->insert([
            'school_name' => 'SMS School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createMinimalSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->string('usertype')->nullable();
            $table->string('id_no')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        });

        Schema::create('student_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('student_year_id')->nullable();
            $table->integer('session_id')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('school_name')->nullable();
            $table->timestamps();
        });

        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->nullable();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->date('event_date')->nullable();
            $table->time('event_time')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->timestamps();
        });

        Schema::create('academic_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('current_session_id')->nullable();
            $table->string('current_term')->nullable();
            $table->timestamps();
        });

        Schema::create('school_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('assign_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('year_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->timestamps();
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('term')->nullable();
            $table->string('title');
            $table->integer('duration')->default(10);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->integer('retake_limit')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default('published');
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->text('question');
            $table->string('option_a')->nullable();
            $table->string('option_b')->nullable();
            $table->string('option_c')->nullable();
            $table->string('option_d')->nullable();
            $table->string('correct_answer')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('quiz_id');
            $table->integer('score')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();
        });

        Schema::create('student_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attempt_id');
            $table->unsignedBigInteger('question_id');
            $table->string('selected_option')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }
}
