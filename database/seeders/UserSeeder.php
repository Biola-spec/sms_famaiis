<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'usertype' => 'Admin',
                'role' => 'admin',
                'password' => Hash::make('12345678'),
                'status' => 1,
            ]
        );
    }
}
