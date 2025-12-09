<?php

namespace Database\Seeders;

use App\Models\RegleConge;
use App\Models\TypeConge;
use Illuminate\Database\Seeder;

class RegleCongeSeeder extends Seeder
{
    public function run(): void
    {
        $paye = TypeConge::where('code', 'PAYE')->first();
        if (!$paye) {
            return;
        }
        RegleConge::updateOrCreate(
            ['type_conge_id' => $paye->id, 'anciennete_min' => 0, 'contrat_type' => null],
            ['jours_acquis_par_mois' => 2.5, 'temps_partiel_ratio' => null]
        );
    }
}
