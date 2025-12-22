<?php

namespace Database\Seeders;

use App\Models\DemandeConge;
use App\Models\TypeConge;
use App\Models\ConsommationConge;
use App\Models\AcquisConge;
use App\Models\CalendrierEvenement;
use Illuminate\Database\Seeder;
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
                'statut' => 'rh_valide', // validé (état final)
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
                'statut' => 'rh_valide', // validation unique (manager)
            ],
        ];

        foreach ($demandes as $d) {
            $demande = DemandeConge::create($d);

            // Si déjà validé RH, consommer l'intégralité des jours demandés pour ce type
            if ($demande->statut === 'rh_valide') {
                $acquis = AcquisConge::where('employe_id', $demande->employe_id)
                    ->where('type_conge_id', $demande->type_conge_id)
                    ->whereDate('expire_first', '>=', Carbon::parse($demande->date_fin))
                    ->orderBy('acquis_first')
                    ->first();

                if ($acquis) {
                    ConsommationConge::create([
                        'demande_conge_id' => $demande->id,
                        'acquis_conge_id' => $acquis->id,
                        'jours_utilises' => $demande->jours_demandes ?? (Carbon::parse($demande->date_debut)->diffInDays(Carbon::parse($demande->date_fin)) + 1),
                    ]);
                }

                CalendrierEvenement::create([
                    'type'        => 'conge',
                    'employe_id'  => $demande->employe_id,
                    'date_debut'  => $demande->date_debut,
                    'date_fin'    => $demande->date_fin,
                    'description' => $demande->typeConge?->libelle ?? 'Congé',
                    'meta'        => [
                        'type_conge_id'     => $demande->type_conge_id,
                        'type_conge_code'   => $demande->typeConge?->code,
                        'type_conge_libelle'=> $demande->typeConge?->libelle,
                        'demande_id'        => $demande->id,
                    ],
                ]);
            }
        }
    }
}
