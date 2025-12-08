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

        for ($i = 1; $i <= 10; $i++) {
            Employe::create([
                'matricule'      => 'EMP-' . now()->format('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nom'            => $faker->lastName(),
                'prenom'         => $faker->firstName(),
                'email'          => $faker->unique()->safeEmail(),
                'telephone'      => $faker->phoneNumber(),
                'adresse'        => $faker->address(),
                'date_naissance' => $faker->date(),
                'poste_id'       => rand(1, 8),
                'departement_id' => rand(1, 5),
                'photo'          => null,
                'date_embauche'  => $faker->date(),
            ]);
        }
    }
}
