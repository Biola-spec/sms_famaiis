<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learnhub_subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('year_id')->nullable()->after('class_id');
            $table->unsignedBigInteger('term_id')->nullable()->after('year_id');

            $table->foreign('year_id')->references('id')->on('student_years')->nullOnDelete();
            if (Schema::hasTable('terms')) {
                $table->foreign('term_id')->references('id')->on('terms')->nullOnDelete();
            }
            $table->index('year_id');
            $table->index('term_id');
        });
    }

    public function down(): void
    {
        Schema::table('learnhub_subjects', function (Blueprint $table) {
            $table->dropForeign(['year_id']);
            $table->dropForeign(['term_id']);
            $table->dropIndex(['year_id']);
            $table->dropIndex(['term_id']);
            $table->dropColumn(['year_id', 'term_id']);
        });
    }
};
