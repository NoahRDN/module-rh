<?php

namespace Database\Seeders;

use App\Models\Departement;
use Illuminate\Database\Seeder;

class DepartementSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nom' => 'Informatique'],
            ['nom' => 'Ressources Humaines'],
            ['nom' => 'Comptabilité'],
            ['nom' => 'Marketing'],
            ['nom' => 'Logistique'],
        ];

        Departement::insert($data);
    }
}
