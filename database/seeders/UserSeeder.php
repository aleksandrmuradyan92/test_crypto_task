<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Test User 1', 'email' => 'test1@example.com', 'password' => 'password'],
            ['name' => 'Test User 2', 'email' => 'test2@example.com', 'password' => 'password'],
            ['name' => 'Test User 3', 'email' => 'test3@example.com', 'password' => 'password'],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                ]
            );
        }
    }
}
