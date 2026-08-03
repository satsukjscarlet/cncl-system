<?php

namespace Database\Seeders;

use App\Models\DistributionCenter;
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
                'center_code' => null,
            ],
            [
                'name' => 'Lãnh đạo',
                'username' => 'lanhdao',
                'email' => 'lanhdao@ntp.local',
                'role' => 'LanhDao',
                'center_code' => null,
            ],
            [
                'name' => 'Trung tâm Nam Phương',
                'username' => 'trungtam_np',
                'email' => 'trungtam.np@ntp.local',
                'role' => 'TrungTam',
                'center_code' => 'NP',
            ],
            [
                'name' => 'Trung tâm Tam Phước',
                'username' => 'trungtam_tp',
                'email' => 'trungtam.tp@ntp.local',
                'role' => 'TrungTam',
                'center_code' => 'TP',
            ],
            [
                'name' => 'Trung tâm Hồng Phước',
                'username' => 'trungtam_hp',
                'email' => 'trungtam.hp@ntp.local',
                'role' => 'TrungTam',
                'center_code' => 'HP',
            ],
            [
                'name' => 'Trung tâm Hà Dung',
                'username' => 'trungtam_hd',
                'email' => 'trungtam.hd@ntp.local',
                'role' => 'TrungTam',
                'center_code' => 'HD',
            ],
            [
                'name' => 'Trung tâm Thái Hoà',
                'username' => 'trungtam_th',
                'email' => 'trungtam.th@ntp.local',
                'role' => 'TrungTam',
                'center_code' => 'TH',
            ],
            [
                'name' => 'Dịch vụ khách hàng',
                'username' => 'dvkh',
                'email' => 'dvkh@ntp.local',
                'role' => 'DVKH',
                'center_code' => null,
            ],
            [
                'name' => 'Phòng thử nghiệm',
                'username' => 'ptn',
                'email' => 'ptn@ntp.local',
                'role' => 'PTN',
                'center_code' => null,
            ],
        ];

        foreach ($users as $item) {
            $centerId = $item['center_code']
                ? DistributionCenter::where('code', $item['center_code'])->value('id')
                : null;

            $user = User::updateOrCreate(
                ['username' => $item['username']],
                [
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'distribution_center_id' => $centerId,
                    'password' => Hash::make('123123123'),
                ]
            );

            $user->syncRoles([$item['role']]);
        }
    }
}
