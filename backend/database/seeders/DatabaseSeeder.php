<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartementSeeder::class,
            PosteSeeder::class,
            EmployeSeeder::class,
            DocumentSeeder::class,
            ContratSeeder::class,
            ContratHistoriqueSeeder::class,
            UserSeeder::class,
            PaieParametreSeeder::class,
            FrequenceCongeSeeder::class,
            TypeCongeSeeder::class,
            RegleCongeSeeder::class,
            CongeAccrualSeeder::class,
            WorktimeSettingsSeeder::class,
            JourFerieSeeder::class,
            DemandeCongeSampleSeeder::class,
            // TestCongeExpirationSeeder::class,
        ]);
    }
}
