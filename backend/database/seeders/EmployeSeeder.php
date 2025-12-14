<?php

namespace Database\Seeders;

use App\Models\Employe;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class EmployeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Génère des employés avec dates cohérentes (18-50 ans) et embauche récente (0-8 ans)
        for ($i = 1; $i <= 10; $i++) {
            $birth = $faker->dateTimeBetween('-50 years', '-20 years');
            $embauche = $faker->dateTimeBetween('-8 years', 'now');

            Employe::create([
                'matricule'      => 'EMP-' . now()->format('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nom'            => $faker->lastName(),
                'prenom'         => $faker->firstName(),
                'email'          => $faker->unique()->safeEmail(),
                'telephone'      => $faker->phoneNumber(),
                'adresse'        => $faker->address(),
                'date_naissance' => $birth->format('Y-m-d'),
                'poste_id'       => rand(1, 8),
                'departement_id' => rand(1, 5),
                'photo'          => null,
                'date_embauche'  => $embauche->format('Y-m-d'),
            ]);
        }
    }
}
