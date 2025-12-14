<?php

namespace Database\Seeders;

use App\Models\JourFerie;
use Illuminate\Database\Seeder;

class JourFerieSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['nom' => 'Nouvel an', 'date' => '2025-01-01', 'recurrent' => true],
            ['nom' => 'Fête du Travail', 'date' => '2025-05-01', 'recurrent' => true],
            ['nom' => 'Fête de l’Indépendance', 'date' => '2025-06-26', 'recurrent' => true],
            ['nom' => 'Noël', 'date' => '2025-12-25', 'recurrent' => true],
        ];

        foreach ($items as $item) {
            JourFerie::updateOrCreate(
                ['date' => $item['date'], 'recurrent' => $item['recurrent']],
                ['nom' => $item['nom']]
            );
        }
    }
}
