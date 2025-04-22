<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TeamUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the users to create with their roles
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('Admin@123'),
                'role_id' => 1, // Admin
                'phone' => '1234567890',
                'email_verified_at' => Carbon::now(),
            ],
            [
                'name' => 'Property Manager',
                'email' => 'manager@example.com',
                'password' => Hash::make('Manager@123'),
                'role_id' => 2, // Property Manager
                'phone' => '2345678901',
                'email_verified_at' => Carbon::now(),
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'password' => Hash::make('User@123'),
                'role_id' => 3, // Regular User
                'phone' => '3456789012',
                'email_verified_at' => Carbon::now(),
            ],
            [
                'name' => 'Content Editor',
                'email' => 'editor@example.com',
                'password' => Hash::make('Editor@123'),
                'role_id' => 4, // Content Editor
                'phone' => '4567890123',
                'email_verified_at' => Carbon::now(),
            ],
            [
                'name' => 'Support Staff',
                'email' => 'support@example.com',
                'password' => Hash::make('Support@123'),
                'role_id' => 5, // Support Staff
                'phone' => '5678901234',
                'email_verified_at' => Carbon::now(),
            ],
        ];

        // Insert users
        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }

        $this->command->info('Team users created successfully!');
        $this->command->table(
            ['Name', 'Email', 'Role ID', 'Password'],
            array_map(function ($user) {
                return [
                    $user['name'],
                    $user['email'],
                    $user['role_id'],
                    'Password hidden for security',
                ];
            }, $users)
        );
    }
} 