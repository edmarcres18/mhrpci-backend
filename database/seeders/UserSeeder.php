<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create System Admin user without using factories (avoids Faker in production)
        User::query()->create([
            'name' => 'System Administrator',
            'email' => 'system.admin@example.com',
            'password' => bcrypt('password'), // Explicitly using bcrypt
            'email_verified_at' => now(),
            'role' => UserRole::SYSTEM_ADMIN,
        ]);

        // Create Admin user
        User::query()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'), // Explicitly using bcrypt
            'email_verified_at' => now(),
            'role' => UserRole::ADMIN,
        ]);

        // Create Staff user
        User::query()->create([
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'password' => bcrypt('password'), // Explicitly using bcrypt
            'email_verified_at' => now(),
            'role' => UserRole::STAFF,
        ]);
    }
}
