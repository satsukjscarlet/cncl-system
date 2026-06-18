<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            UserSeeder::class,
            DistributionCenterSeeder::class,
            QualityStandardSeeder::class,
            ProductGroupSeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            SlaConfigSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
