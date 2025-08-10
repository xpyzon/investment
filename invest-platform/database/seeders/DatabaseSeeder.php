<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
                'kyc_status' => 'verified',
                'is_active' => true,
            ]);
        }

        // Example investor
        if (!User::where('email', 'investor@example.com')->exists()) {
            User::create([
                'name' => 'Investor One',
                'email' => 'investor@example.com',
                'password' => Hash::make('Investor@12345'),
                'role' => 'investor',
                'kyc_status' => 'unverified',
                'is_active' => true,
            ]);
        }
    }
}
