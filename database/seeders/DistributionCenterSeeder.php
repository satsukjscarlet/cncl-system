<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DistributionCenter;

class DistributionCenterSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'code' => 'HP',
                'name' => 'Trung tâm Hải Phòng',
            ],
            [
                'code' => 'HN',
                'name' => 'Trung tâm Hà Nội',
            ],
            [
                'code' => 'DN',
                'name' => 'Trung tâm Đà Nẵng',
            ],
            [
                'code' => 'HCM',
                'name' => 'Trung tâm Hồ Chí Minh',
            ],
        ];

        foreach ($data as $item) {
            DistributionCenter::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}