<?php

namespace Database\Seeders;

use App\Models\Contrat;
use Illuminate\Database\Seeder;

class ContratSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Contrat::create([
                'numero' => 'CTR-' . now()->format('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'employe_id' => $i,
                'type_contrat' => 'CDI',
                'date_debut' => now()->subYears(1),
                'date_fin' => null,
                'periode_essai_debut' => null,
                'periode_essai_fin' => null,
                'renouvelable' => false,
                'salaire_base' => 750000,
            ]);
        }
    }
}
