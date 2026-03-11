<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name'              => 'Admin User',
            'username'          => 'admin',
            'email'             => 'admin@cinema.local',
            'password'          => Hash::make('password'),
            'role'              => 'ADMIN',
            'is_active'         => true,
            'reputation'        => 1000,
            'email_verified_at' => now(),
        ]);

        // Moderator
        User::create([
            'name'              => 'Moderator User',
            'username'          => 'moderator',
            'email'             => 'mod@cinema.local',
            'password'          => Hash::make('password'),
            'role'              => 'MODERATOR',
            'is_active'         => true,
            'reputation'        => 500,
            'email_verified_at' => now(),
        ]);

        // Regular User
        User::create([
            'name'              => 'Regular User',
            'username'          => 'user',
            'email'             => 'user@cinema.local',
            'password'          => Hash::make('password'),
            'role'              => 'USER',
            'is_active'         => true,
            'reputation'        => 50,
            'email_verified_at' => now(),
        ]);
    }
}
