<?php

namespace Database\Seeders;

use App\Models\Poste;
use Illuminate\Database\Seeder;

class PosteSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nom' => 'Développeur', 'departement_id' => 1, 'categorie' => 'TAM', 'categorie_level' => 3],
            ['nom' => 'Tech Lead', 'departement_id' => 1, 'categorie' => 'Cadres', 'categorie_level' => 4],
            ['nom' => 'RH Junior', 'departement_id' => 2, 'categorie' => 'Employés', 'categorie_level' => 2],
            ['nom' => 'RH Manager', 'departement_id' => 2, 'categorie' => 'Cadres', 'categorie_level' => 4],
            ['nom' => 'Comptable', 'departement_id' => 3, 'categorie' => 'Employés', 'categorie_level' => 2],
            ['nom' => 'Contrôleur de gestion', 'departement_id' => 3, 'categorie' => 'TAM', 'categorie_level' => 3],
            ['nom' => 'Assistant Marketing', 'departement_id' => 4, 'categorie' => 'Employés', 'categorie_level' => 2],
            ['nom' => 'Responsable Logistique', 'departement_id' => 5, 'categorie' => 'Cadres', 'categorie_level' => 4],
        ];

        Poste::insert($data);
    }
}
