<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
            DepartmentPositionSeeder::class,
            BreedDefaultSeeder::class,
            SampleDataSeeder::class,
            ZoonosisSeeder::class,
            VaccineProtocolSeeder::class,
            ConsentTemplateSeeder::class,
            GeoSeeder::class,
            AmigoBichoSettingsSeeder::class,
        ]);
    }
}
