<?php

namespace Database\Seeders;

use App\Models\FrequenceConge;
use Illuminate\Database\Seeder;

class FrequenceCongeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'MOIS', 'libelle' => 'Par mois', 'description' => 'Crédit ou limite mensuelle'],
            ['code' => 'AN', 'libelle' => 'Par an', 'description' => 'Limite annuelle'],
            ['code' => 'EVENEMENT', 'libelle' => 'Par événement', 'description' => 'Mariage, décès, naissance'],
            ['code' => 'CERTIFICAT', 'libelle' => 'Certificat médical', 'description' => 'Selon justificatif'],
            ['code' => 'GROSSESSE', 'libelle' => 'Par grossesse', 'description' => 'Maternité'],
            ['code' => 'NAISSANCE', 'libelle' => 'Par naissance', 'description' => 'Paternité'],
            ['code' => 'MANAGER', 'libelle' => 'Selon manager', 'description' => 'Validation hiérarchie'],
            ['code' => 'FORMATION', 'libelle' => 'Par formation', 'description' => 'Congé formation'],
        ];
        foreach ($items as $item) {
            FrequenceConge::updateOrCreate(['code' => $item['code']], $item);
        }
    }
}
