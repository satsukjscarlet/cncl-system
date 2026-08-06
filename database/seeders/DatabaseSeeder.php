<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            DistributionCenterSeeder::class,
            UserSeeder::class,
            QualityStandardSeeder::class,
            ProductGroupSeeder::class,
            UrgentReasonSeeder::class,
            SlaConfigSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
