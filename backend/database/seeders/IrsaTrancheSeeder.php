<?php

namespace Database\Seeders;

use App\Models\IrsaTranche;
use Illuminate\Database\Seeder;

class IrsaTrancheSeeder extends Seeder
{
    public function run(): void
    {
        // Exemples simples de tranches IRSA
        $data = [
            ['min_base' => 0,       'max_base' => 350000,  'taux' => 0],
            ['min_base' => 350001,  'max_base' => 400000,  'taux' => 5],
            ['min_base' => 400001,  'max_base' => 500000,  'taux' => 10],
            ['min_base' => 500001,  'max_base' => 600000,  'taux' => 15],
            ['min_base' => 600001,  'max_base' => 4000000, 'taux' => 20],
            ['min_base' => 4000001, 'max_base' => null,    'taux' => 25],
        ];

        foreach ($data as $row) {
            IrsaTranche::firstOrCreate(
                ['min_base' => $row['min_base'], 'max_base' => $row['max_base']],
                ['taux' => $row['taux']]
            );
        }
    }
}
