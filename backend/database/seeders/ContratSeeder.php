<?php

namespace Database\Seeders;

use App\Models\Contrat;
use Illuminate\Database\Seeder;

class ContratSeeder extends Seeder
{
    public function run(): void
    {
        // Jeu varié : CDI, CDD (avec/ sans essai), Stage
        $now = now();
        $contracts = [
            // Employé 1 : CDI sans essai, 3 ans
            [
                'employe_id' => 1, 'type_contrat' => 'CDI',
                'date_debut' => $now->copy()->subYears(3)->toDateString(),
                'date_fin' => null,
                'periode_essai_debut' => null,
                'periode_essai_fin' => null,
                'salaire_base' => 850000,
            ],
            // Employé 2 : CDD 6 mois avec essai 1 mois (actif)
            [
                'employe_id' => 2, 'type_contrat' => 'CDD',
                'date_debut' => $now->copy()->subMonths(5)->toDateString(),
                'date_fin' => $now->copy()->addMonth()->toDateString(),
                'periode_essai_debut' => $now->copy()->subMonths(5)->toDateString(),
                'periode_essai_fin' => $now->copy()->subMonths(4)->toDateString(),
                'salaire_base' => 600000,
            ],
            // Employé 3 : CDD 3 mois sans essai (actif)
            [
                'employe_id' => 3, 'type_contrat' => 'CDD',
                'date_debut' => $now->copy()->subMonth()->startOfMonth()->toDateString(),
                'date_fin' => $now->copy()->addMonths(2)->endOfMonth()->toDateString(),
                'periode_essai_debut' => null,
                'periode_essai_fin' => null,
                'salaire_base' => 500000,
            ],
            // Employé 4 : CDI avec essai 2 mois (actif)
            [
                'employe_id' => 4, 'type_contrat' => 'CDI',
                'date_debut' => $now->copy()->subMonths(2)->toDateString(),
                'date_fin' => null,
                'periode_essai_debut' => $now->copy()->subMonths(2)->toDateString(),
                'periode_essai_fin' => $now->copy()->subMonth()->toDateString(),
                'salaire_base' => 900000,
            ],
            // Employé 5 : Stage 6 mois (actif)
            [
                'employe_id' => 5, 'type_contrat' => 'Stage',
                'date_debut' => $now->copy()->subMonths(1)->toDateString(),
                'date_fin' => $now->copy()->addMonths(5)->toDateString(),
                'periode_essai_debut' => null,
                'periode_essai_fin' => null,
                'salaire_base' => 300000,
            ],
            // Employé 6 : CDD passé (inactif, terminé il y a 1 an)
            [
                'employe_id' => 6, 'type_contrat' => 'CDD',
                'date_debut' => $now->copy()->subYears(1)->subMonths(3)->toDateString(),
                'date_fin' => $now->copy()->subYear()->toDateString(),
                'periode_essai_debut' => $now->copy()->subYears(1)->subMonths(3)->toDateString(),
                'periode_essai_fin' => $now->copy()->subYears(1)->subMonths(2)->toDateString(),
                'salaire_base' => 550000,
            ],
            // Employé 7 : CDI ancien (4 ans) sans essai
            [
                'employe_id' => 7, 'type_contrat' => 'CDI',
                'date_debut' => $now->copy()->subYears(4)->toDateString(),
                'date_fin' => null,
                'periode_essai_debut' => null,
                'periode_essai_fin' => null,
                'salaire_base' => 780000,
            ],
            // Employé 8 : CDD 12 mois en cours avec essai 1 mois
            [
                'employe_id' => 8, 'type_contrat' => 'CDD',
                'date_debut' => $now->copy()->subMonths(3)->toDateString(),
                'date_fin' => $now->copy()->addMonths(9)->toDateString(),
                'periode_essai_debut' => $now->copy()->subMonths(3)->toDateString(),
                'periode_essai_fin' => $now->copy()->subMonths(2)->toDateString(),
                'salaire_base' => 650000,
            ],
            // Employé 9 : Stage terminé (archive)
            [
                'employe_id' => 9, 'type_contrat' => 'Stage',
                'date_debut' => $now->copy()->subMonths(8)->toDateString(),
                'date_fin' => $now->copy()->subMonths(2)->toDateString(),
                'periode_essai_debut' => null,
                'periode_essai_fin' => null,
                'salaire_base' => 250000,
            ],
            // Employé 10 : CDI récent sans essai
            [
                'employe_id' => 10, 'type_contrat' => 'CDI',
                'date_debut' => $now->copy()->subMonth()->toDateString(),
                'date_fin' => null,
                'periode_essai_debut' => null,
                'periode_essai_fin' => null,
                'salaire_base' => 720000,
            ],
        ];

        foreach ($contracts as $idx => $c) {
            Contrat::create([
                'numero' => 'CTR-' . now()->format('Ymd') . '-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'employe_id' => $c['employe_id'],
                'type_contrat' => $c['type_contrat'],
                'date_debut' => $c['date_debut'],
                'date_fin' => $c['date_fin'],
                'periode_essai_debut' => $c['periode_essai_debut'],
                'periode_essai_fin' => $c['periode_essai_fin'],
                'renouvelable' => $c['type_contrat'] === 'CDD',
                'salaire_base' => $c['salaire_base'],
                'statut' => ($c['date_fin'] && now()->gt($c['date_fin'])) ? 'termine' : 'en_cours',
            ]);
        }
    }
}
