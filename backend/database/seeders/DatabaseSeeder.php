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
        // Les donnees applicatives sont chargees via backend/database/*.sql
        // et ne sont donc pas reinseres par les seeders Laravel.
    }
}
