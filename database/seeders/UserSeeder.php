<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /* ============================
         |  ADMIN USER
         ============================ */
        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin',
                'phone' => '01099999999',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone_verified' => true,
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        /* ============================
         |  NORMAL USER
         ============================ */
        User::updateOrCreate(
            ['email' => 'user@test.com'],
            [
                'name' => 'Normal User',
                'phone' => '01011111111',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'phone_verified' => true,
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );
    }
}
