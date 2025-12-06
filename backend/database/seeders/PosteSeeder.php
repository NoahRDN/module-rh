<?php

namespace Database\Seeders;

use App\Models\Poste;
use Illuminate\Database\Seeder;

class PosteSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nom' => 'Développeur', 'departement_id' => 1],
            ['nom' => 'Tech Lead', 'departement_id' => 1],
            ['nom' => 'RH Junior', 'departement_id' => 2],
            ['nom' => 'RH Manager', 'departement_id' => 2],
            ['nom' => 'Comptable', 'departement_id' => 3],
            ['nom' => 'Contrôleur de gestion', 'departement_id' => 3],
            ['nom' => 'Assistant Marketing', 'departement_id' => 4],
            ['nom' => 'Responsable Logistique', 'departement_id' => 5],
        ];

        Poste::insert($data);
    }
}
