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
            PointageSeeder::class,
            DocumentSeeder::class,
            ContratSeeder::class,
            ContratHistoriqueSeeder::class,
            UserSeeder::class,
            PaieParametreSeeder::class,
            IrsaTrancheSeeder::class,
            RemunerationItemSeeder::class,
            FrequenceCongeSeeder::class,
            TypeCongeSeeder::class,
            RegleCongeSeeder::class,
            CongeAccrualSeeder::class,
            WorktimeSettingsSeeder::class,
            JourFerieSeeder::class,
            DemandeCongeSampleSeeder::class,
            CompetenceSeeder::class,
            TestDataSeeder::class,
            // TestCongeExpirationSeeder::class,
        ]);
    }
}
