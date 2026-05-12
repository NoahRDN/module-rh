<?php

namespace Database\Seeders;

use App\Models\Contrat;
use App\Models\Employe;
use App\Models\Poste;
use App\Models\RemunerationItem;
use Illuminate\Database\Seeder;

class RemunerationItemSeeder extends Seeder
{
    public function run(): void
    {
        $currentMonth = now()->format('Y-m');

        $developpeur = Poste::query()->where('nom', 'Développeur')->first();
        $rhManager = Poste::query()->where('nom', 'RH Manager')->first();
        $mika = Employe::query()->where('matricule', 'EMP-20260429-0001')->first();
        $tokyContract = Contrat::query()
            ->where('employe_id', optional(Employe::query()->where('matricule', 'EMP-20260429-0008')->first())->id)
            ->where('statut', 'en_cours')
            ->latest('id')
            ->first();

        $items = [
            [
                'libelle' => 'Indemnité transport',
                'nature' => 'indemnite',
                'scope_type' => 'global',
                'poste_id' => null,
                'employe_id' => null,
                'contrat_id' => null,
                'recurrence_type' => 'recurrent',
                'mois_application' => null,
                'condition_type' => null,
                'condition_operator' => null,
                'condition_value' => null,
                'montant' => 50000,
                'calculation_type' => 'fixe',
                'prorata' => false,
                'depends_on_presence' => false,
                'is_taxable' => false,
                'actif' => true,
            ],
            [
                'libelle' => 'Prime présence',
                'nature' => 'prime',
                'scope_type' => 'global',
                'poste_id' => null,
                'employe_id' => null,
                'contrat_id' => null,
                'recurrence_type' => 'recurrent',
                'mois_application' => null,
                'condition_type' => null,
                'condition_operator' => null,
                'condition_value' => null,
                'montant' => 80000,
                'calculation_type' => 'fixe',
                'prorata' => true,
                'depends_on_presence' => true,
                'is_taxable' => true,
                'actif' => true,
            ],
            [
                'libelle' => 'Prime ancienneté',
                'nature' => 'prime',
                'scope_type' => 'global',
                'poste_id' => null,
                'employe_id' => null,
                'contrat_id' => null,
                'recurrence_type' => 'recurrent',
                'mois_application' => null,
                'condition_type' => 'anciennete',
                'condition_operator' => '>=',
                'condition_value' => 2,
                'montant' => 60000,
                'calculation_type' => 'fixe',
                'prorata' => false,
                'depends_on_presence' => false,
                'is_taxable' => true,
                'actif' => true,
            ],
            [
                'libelle' => 'Prime connexion développeur',
                'nature' => 'indemnite',
                'scope_type' => 'poste',
                'poste_id' => $developpeur?->id,
                'employe_id' => null,
                'contrat_id' => null,
                'recurrence_type' => 'recurrent',
                'mois_application' => null,
                'condition_type' => null,
                'condition_operator' => null,
                'condition_value' => null,
                'montant' => 40000,
                'calculation_type' => 'fixe',
                'prorata' => false,
                'depends_on_presence' => false,
                'is_taxable' => false,
                'actif' => (bool) $developpeur,
            ],
            [
                'libelle' => 'Prime responsabilité RH',
                'nature' => 'prime',
                'scope_type' => 'poste',
                'poste_id' => $rhManager?->id,
                'employe_id' => null,
                'contrat_id' => null,
                'recurrence_type' => 'recurrent',
                'mois_application' => null,
                'condition_type' => null,
                'condition_operator' => null,
                'condition_value' => null,
                'montant' => 120000,
                'calculation_type' => 'fixe',
                'prorata' => false,
                'depends_on_presence' => false,
                'is_taxable' => true,
                'actif' => (bool) $rhManager,
            ],
            [
                'libelle' => 'Prime performance exceptionnelle',
                'nature' => 'prime',
                'scope_type' => 'employe',
                'poste_id' => null,
                'employe_id' => $mika?->id,
                'contrat_id' => null,
                'recurrence_type' => 'ponctuel',
                'mois_application' => $currentMonth,
                'condition_type' => null,
                'condition_operator' => null,
                'condition_value' => null,
                'montant' => 150000,
                'calculation_type' => 'fixe',
                'prorata' => false,
                'depends_on_presence' => false,
                'is_taxable' => true,
                'actif' => (bool) $mika,
            ],
            [
                'libelle' => 'Indemnité logement contrat',
                'nature' => 'indemnite',
                'scope_type' => 'contrat',
                'poste_id' => null,
                'employe_id' => null,
                'contrat_id' => $tokyContract?->id,
                'recurrence_type' => 'recurrent',
                'mois_application' => null,
                'condition_type' => null,
                'condition_operator' => null,
                'condition_value' => null,
                'montant' => 100000,
                'calculation_type' => 'fixe',
                'prorata' => false,
                'depends_on_presence' => false,
                'is_taxable' => false,
                'actif' => (bool) $tokyContract,
            ],
        ];

        foreach ($items as $item) {
            $identity = [
                'libelle' => $item['libelle'],
                'scope_type' => $item['scope_type'],
                'poste_id' => $item['poste_id'],
                'employe_id' => $item['employe_id'],
                'contrat_id' => $item['contrat_id'],
                'recurrence_type' => $item['recurrence_type'],
                'mois_application' => $item['mois_application'],
            ];

            RemunerationItem::query()->updateOrCreate($identity, $item);
        }
    }
}
