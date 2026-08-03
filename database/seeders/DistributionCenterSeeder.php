<?php

namespace Database\Seeders;

use App\Models\DistributionCenter;
use Illuminate\Database\Seeder;

class DistributionCenterSeeder extends Seeder
{
    public function run(): void
    {
        $centers = [
            [
                'code' => 'NP',
                'name' => 'Nam Phương',
            ],
            [
                'code' => 'TP',
                'name' => 'Tam Phước',
            ],
            [
                'code' => 'HP',
                'name' => 'Hồng Phước',
            ],
            [
                'code' => 'HD',
                'name' => 'Hà Dung',
            ],
            [
                'code' => 'TH',
                'name' => 'Thái Hoà',
            ],
        ];

        foreach ($centers as $center) {
            DistributionCenter::updateOrCreate(
                ['code' => $center['code']],
                [
                    'name' => $center['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
