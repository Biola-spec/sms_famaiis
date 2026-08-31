<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'starts_at')) {
                $table->dateTime('starts_at')->nullable()->after('duration');
            }
            if (!Schema::hasColumn('quizzes', 'ends_at')) {
                $table->dateTime('ends_at')->nullable()->after('starts_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'starts_at')) {
                $table->dropColumn('starts_at');
            }
            if (Schema::hasColumn('quizzes', 'ends_at')) {
                $table->dropColumn('ends_at');
            }
        });
    }
};
