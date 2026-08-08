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
        $testEmail = 'thientuantest@gmail.com';
        $defaultPassword = '123123123';

        $users = [
            [
                'name' => 'Quản trị hệ thống',
                'username' => 'admin',
                'role' => 'Admin',
                'center_code' => null,
                'smartca_user_id' => null,
            ],
            [
                'name' => 'Lãnh đạo',
                'username' => 'lanhdao',
                'role' => 'LanhDao',
                'center_code' => null,
                'smartca_user_id' => null,
            ],
            [
                'name' => 'Trung tâm Nam Phương',
                'username' => 'trungtam_np',
                'role' => 'TrungTam',
                'center_code' => 'NP',
                'smartca_user_id' => null,
            ],
            [
                'name' => 'Trung tâm Tam Phước',
                'username' => 'trungtam_tp',
                'role' => 'TrungTam',
                'center_code' => 'TP',
                'smartca_user_id' => null,
            ],
            [
                'name' => 'Trung tâm Hồng Phước',
                'username' => 'trungtam_hp',
                'role' => 'TrungTam',
                'center_code' => 'HP',
                'smartca_user_id' => null,
            ],
            [
                'name' => 'Trung tâm Hà Dung',
                'username' => 'trungtam_hd',
                'role' => 'TrungTam',
                'center_code' => 'HD',
                'smartca_user_id' => null,
            ],
            [
                'name' => 'Trung tâm Thái Hoà',
                'username' => 'trungtam_th',
                'role' => 'TrungTam',
                'center_code' => 'TH',
                'smartca_user_id' => null,
            ],
            [
                'name' => 'Dịch vụ khách hàng',
                'username' => 'dvkh',
                'role' => 'DVKH',
                'center_code' => null,
                'smartca_user_id' => null,
            ],
            [
                'name' => 'Phòng thử nghiệm',
                'username' => 'ptn',
                'role' => 'PTN',
                'center_code' => null,
                'smartca_user_id' => null,
            ],
            [
                'name' => 'Trưởng phòng thử nghiệm',
                'username' => 'truongptn',
                'role' => 'TruongPTN',
                'center_code' => null,
                // Điền CCCD/MST/SĐT SmartCA thật trên màn Người dùng trước khi test ký số.
                'smartca_user_id' => null,
            ],
            [
                'name' => 'Tài khoản chỉ xem',
                'username' => 'viewer',
                'role' => 'Viewer',
                'center_code' => null,
                'smartca_user_id' => null,
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
                    'email' => $testEmail,
                    'distribution_center_id' => $centerId,
                    'smartca_user_id' => $item['smartca_user_id'],
                    'is_active' => true,
                    'password' => Hash::make($defaultPassword),
                ]
            );

            $user->syncRoles([$item['role']]);
        }
    }
}
