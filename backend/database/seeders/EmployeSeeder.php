<?php

namespace Database\Seeders;

use App\Models\Employe;
use App\Models\User;
use App\Models\HistoriquePoste;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class EmployeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $posteMax = 8;

        // Génère des employés avec dates cohérentes (18-50 ans) et embauche récente (0-8 ans)
        for ($i = 1; $i <= 10; $i++) {
            $birth = $faker->dateTimeBetween('-50 years', '-20 years');
            $embauche = $faker->dateTimeBetween('-8 years', 'now');

            $email = $faker->unique()->safeEmail();
            $employe = Employe::create([
                'matricule'      => 'EMP-' . now()->format('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nom'            => $faker->lastName(),
                'prenom'         => $faker->firstName(),
                'email'          => $email,
                'telephone'      => $faker->phoneNumber(),
                'adresse'        => $faker->address(),
                'date_naissance' => $birth->format('Y-m-d'),
                'poste_id'       => $i <= $posteMax ? $i : rand(1, $posteMax),
                'departement_id' => rand(1, 5),
                'num_cnaps'      => 'CNAPS-' . str_pad((string) $faker->unique()->randomNumber(6), 6, '0', STR_PAD_LEFT),
                'photo'          => null,
                'date_embauche'  => $embauche->format('Y-m-d'),
            ]);

            // Créer un utilisateur lié
            User::create([
                'name' => $employe->nom . ' ' . $employe->prenom,
                'email' => $email,
                'password' => env('DEFAULT_USER_PASSWORD', 'password'),
                'role' => 'employe',
                'employe_id' => $employe->id,
            ]);

            // Historique de poste initial
            HistoriquePoste::create([
                'employe_id' => $employe->id,
                'poste_id' => $employe->poste_id,
                'departement_id' => $employe->departement_id,
                'date_changement' => $employe->date_embauche,
                'motif' => 'Affectation initiale',
            ]);
        }
    }
}
