<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Quản trị hệ thống',
                'email' => 'thientuantest@gmail.com',
                'password' => Hash::make('12345678'),
            ]
        );

        $user->assignRole('Admin');
    }
}
