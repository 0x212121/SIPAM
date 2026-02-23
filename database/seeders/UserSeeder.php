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
            [
                'name' => 'Admin User',
                'email' => 'admin@audit.local',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ],
            [
                'name' => 'Auditor User',
                'email' => 'auditor@audit.local',
                'password' => Hash::make('auditor123'),
                'role' => 'auditor',
            ],
            [
                'name' => 'Reviewer User',
                'email' => 'reviewer@audit.local',
                'password' => Hash::make('reviewer123'),
                'role' => 'reviewer',
            ],
            [
                'name' => 'Readonly User',
                'email' => 'readonly@audit.local',
                'password' => Hash::make('readonly123'),
                'role' => 'readonly',
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);
            
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            
            $user->assignRole($role);
        }
    }
}
