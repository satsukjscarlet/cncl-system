<?php

namespace Database\Seeders;

use App\Models\QualityStandard;
use Illuminate\Database\Seeder;

class QualityStandardSeeder extends Seeder
{
    public function run(): void
    {
        $standards = [
            'ISO 1452-2:2009',
            'ISO 1452-3:2009',
            'ISO 1452-4:2009',
            'ISO 4427-2:2019',
            'ISO 4427-3:2019',
            'DIN 8077:2008',
            'DIN 8078:2008',
            'DIN 16962:2000',
            'EN 1074-2:2000',
            'BS EN 124:2015',
            'TCVN 8491-3:2011',
            'TCVN 7417-1:2010',
            'TCVN 12653-1,2:2024',
            'TCCS138:2014/NTP',
            'TCCS02:2010/NTP',
            'TCCS24:2013/NTP',
            'TCCS 18:2020/NTP',
            'TCCS50:2010/NTP',
            'TCCS17/2020/NTP',
            'TCVN 14489:2025',
        ];

        foreach ($standards as $standard) {
            QualityStandard::updateOrCreate(
                ['code' => $standard],
                [
                    'name' => $standard,
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}