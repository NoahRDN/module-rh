<?php

namespace Database\Seeders;

use App\Models\DemandeConge;
use App\Models\TypeConge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemandeCongeSampleSeeder extends Seeder
{
    public function run(): void
    {
        $payeId = TypeConge::where('code', 'PAYE')->value('id');
        $sansSoldeId = TypeConge::where('code', 'SANS_SOLDE')->value('id');
        $maladieId = TypeConge::where('code', 'MALADIE')->value('id');

        if (!$payeId) {
            return;
        }

        $demandes = [
            // Employé 2 - congé payé validé RH
            [
                'employe_id' => 2,
                'type_conge_id' => $payeId,
                'date_debut' => Carbon::now()->addDays(3)->toDateString(),
                'date_fin' => Carbon::now()->addDays(5)->toDateString(),
                'jours_demandes' => 3,
                'motif' => 'Repos planifié',
                'statut' => 'rh_valide',
            ],
            // Employé 3 - congé sans solde en attente
            [
                'employe_id' => 3,
                'type_conge_id' => $sansSoldeId ?? $payeId,
                'date_debut' => Carbon::now()->addDays(10)->toDateString(),
                'date_fin' => Carbon::now()->addDays(12)->toDateString(),
                'jours_demandes' => 3,
                'motif' => 'Projet personnel',
                'statut' => 'en_attente',
            ],
            // Employé 4 - congé maladie validé manager
            [
                'employe_id' => 4,
                'type_conge_id' => $maladieId ?? $payeId,
                'date_debut' => Carbon::now()->subDays(4)->toDateString(),
                'date_fin' => Carbon::now()->subDays(2)->toDateString(),
                'jours_demandes' => 3,
                'motif' => 'Certificat médical',
                'statut' => 'manager_valide',
            ],
        ];

        foreach ($demandes as $d) {
            DemandeConge::create($d);
        }
    }
}
