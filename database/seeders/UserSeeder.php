<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Dev-only default admin. Production must set INITIAL_ADMIN_EMAIL/PASSWORD
     * (min 8 chars) — see SECURITY_REVIEW.md. This seeder never runs in
     * production (gated below) and never overwrites an existing password.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }
        $email = env('INITIAL_ADMIN_EMAIL', 'admin@gmail.com');
        $password = env('INITIAL_ADMIN_PASSWORD', '');
        if ($password !== '' && strlen($password) < 8) {
            $password = '';
        }
        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'phone' => '01000000000',
                'password' => Hash::make($password !== '' ? $password : 'Admin1234'),
                'user_type' => 1,
                'status' => 1,
            ]
        );
    }
}
