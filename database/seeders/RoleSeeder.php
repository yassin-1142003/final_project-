<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if roles table exists
        if (!Schema::hasTable('roles')) {
            $this->command->error('Roles table does not exist. Please run migrations first.');
            return;
        }

        // Define roles
        $roles = [
            [
                'id' => 1,
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Administrator with full access to all features',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'name' => 'property_manager',
                'display_name' => 'Property Manager',
                'description' => 'Can manage properties and listings',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 3,
                'name' => 'user',
                'display_name' => 'Regular User',
                'description' => 'Standard user with basic access',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 4,
                'name' => 'content_editor',
                'display_name' => 'Content Editor',
                'description' => 'Can edit and manage content',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 5,
                'name' => 'support_staff',
                'display_name' => 'Support Staff',
                'description' => 'Handles customer support and requests',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        // Insert roles if they don't exist
        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                $role
            );
        }

        $this->command->info('Roles seeded successfully!');
    }
} 