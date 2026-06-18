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
                'name' => 'Quản trị hệ thống',
                'username' => 'admin',
                'email' => 'admin@ntp.local',
                'role' => 'Admin',
            ],
            [
                'name' => 'Lãnh đạo',
                'username' => 'lanhdao',
                'email' => 'lanhdao@ntp.local',
                'role' => 'LanhDao',
            ],
            [
                'name' => 'Trung tâm Hải Phòng',
                'username' => 'trungtam_hp',
                'email' => 'trungtam.hp@ntp.local',
                'role' => 'TrungTam',
            ],
            [
                'name' => 'Trung tâm Hà Nội',
                'username' => 'trungtam_hn',
                'email' => 'trungtam.hn@ntp.local',
                'role' => 'TrungTam',
            ],
            [
                'name' => 'Trung tâm Hồ Chí Minh',
                'username' => 'trungtam_hcm',
                'email' => 'trungtam.hcm@ntp.local',
                'role' => 'TrungTam',
            ],
            [
                'name' => 'Dịch vụ khách hàng',
                'username' => 'dvkh',
                'email' => 'dvkh@ntp.local',
                'role' => 'DVKH',
            ],
            [
                'name' => 'Phòng thử nghiệm',
                'username' => 'ptn',
                'email' => 'ptn@ntp.local',
                'role' => 'PTN',
            ],
        ];

        foreach ($users as $item) {
            $user = User::updateOrCreate(
                ['username' => $item['username']],
                [
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'password' => Hash::make('123123123'),
                ]
            );

            $user->syncRoles([$item['role']]);
        }
    }
}