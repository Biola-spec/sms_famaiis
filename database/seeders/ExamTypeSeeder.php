<?php

namespace Database\Seeders;

use App\Models\ExamType;
use Illuminate\Database\Seeder;

class ExamTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'CA1', 'description' => 'Continuous Assessment 1', 'active' => true],
            ['name' => 'CA2', 'description' => 'Continuous Assessment 2', 'active' => true],
            ['name' => 'CA3', 'description' => 'Continuous Assessment 3', 'active' => true],
            ['name' => 'Midterm', 'description' => 'Midterm Examination', 'active' => true],
            ['name' => 'Final', 'description' => 'Final Examination', 'active' => true],
            ['name' => 'Project', 'description' => 'Project-based assessment', 'active' => true],
        ];

        if (\Illuminate\Support\Facades\Schema::hasTable('exam_types') && class_exists('App\Models\ExamType')) {
            foreach ($items as $item) {
                ExamType::query()->updateOrCreate(['name' => $item['name']], $item);
            }
        }
    }
}
