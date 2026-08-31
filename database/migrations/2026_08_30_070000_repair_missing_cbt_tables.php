<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!$this->tableExists('questions')) {
            Schema::create('questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
                $table->text('question');
                $table->text('option_a')->nullable();
                $table->string('image_a')->nullable();
                $table->text('option_b')->nullable();
                $table->string('image_b')->nullable();
                $table->text('option_c')->nullable();
                $table->string('image_c')->nullable();
                $table->text('option_d')->nullable();
                $table->string('image_d')->nullable();
                $table->text('option_e')->nullable();
                $table->string('image_e')->nullable();
                $table->string('correct_answer')->nullable();
                $table->string('image')->nullable();
                $table->timestamps();
            });
        }

        if (!$this->tableExists('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
                $table->integer('score')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->string('status')->default('in-progress');
                $table->timestamps();
            });
        }

        if (!$this->tableExists('student_answers')) {
            Schema::create('student_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attempt_id')->constrained('quiz_attempts')->onDelete('cascade');
                $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
                $table->string('selected_option')->nullable();
                $table->boolean('is_correct')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Repair migration does not drop existing tables.
    }

    private function tableExists(string $table): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', DB::raw('database()'))
            ->where('table_name', $table)
            ->exists();
    }
};
