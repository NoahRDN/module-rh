<?php

namespace Database\Seeders;

use App\Models\TypeConge;
use App\Models\FrequenceConge;
use Illuminate\Database\Seeder;

class TypeCongeSeeder extends Seeder
{
    public function run(): void
    {
        $freqMap = FrequenceConge::pluck('id', 'code');

        $types = [
            ['libelle' => 'Congé payé', 'code' => 'PAYE', 'utilise_solde' => true, 'paye' => true, 'frequence_code' => 'MOIS', 'jours_forfait' => 2.5, 'description' => '2,5 jours/mois cumulables 3 ans', 'cumulable' => true, 'cumulable_duree' => 36, 'cumulable_frequence_code' => 'MOIS'],
            ['libelle' => 'Congé sans solde', 'code' => 'SANS_SOLDE', 'utilise_solde' => false, 'paye' => false, 'frequence_code' => 'MANAGER'],

            // Modèle A : événements distincts
            ['libelle' => 'Congé mariage', 'code' => 'MARIAGE', 'utilise_solde' => false, 'paye' => true, 'frequence_code' => 'EVENEMENT', 'jours_forfait' => 3, 'limite' => 1, 'limite_frequence_code' => 'AN', 'description' => '1 fois par an, 3 jours'],
            ['libelle' => 'Congé décès (proche)', 'code' => 'DECES', 'utilise_solde' => false, 'paye' => true, 'frequence_code' => 'EVENEMENT', 'jours_forfait' => 5, 'limite' => 4, 'limite_frequence_code' => 'AN', 'description' => 'Jusqu’à 4 décès par an, 5 jours chacun'],
            ['libelle' => 'Congé naissance', 'code' => 'NAISSANCE', 'utilise_solde' => false, 'paye' => true, 'frequence_code' => 'NAISSANCE', 'jours_forfait' => 3, 'limite' => 2, 'limite_frequence_code' => 'AN', 'description' => '2 naissances max par an, 3 jours chacune'],
            ['libelle' => 'Congé déménagement', 'code' => 'DEMENAGEMENT', 'utilise_solde' => false, 'paye' => true, 'frequence_code' => 'EVENEMENT', 'jours_forfait' => 1, 'limite' => 1, 'limite_frequence_code' => 'AN', 'description' => '1 fois par an, 1 jour'],

            ['libelle' => 'Congé maladie', 'code' => 'MALADIE', 'utilise_solde' => false, 'paye' => true, 'justificatif_obligatoire' => true, 'frequence_code' => 'CERTIFICAT'],
            ['libelle' => 'Congé maternité', 'code' => 'MATERNITE', 'utilise_solde' => false, 'paye' => true, 'sexe_autorise' => 'femme', 'frequence_code' => 'GROSSESSE', 'jours_forfait' => 98, 'description' => 'Durée légale indicative 14 semaines', 'limite' => 1, 'limite_frequence_code' => 'GROSSESSE'],
            ['libelle' => 'Congé paternité', 'code' => 'PATERNITE', 'utilise_solde' => false, 'paye' => true, 'sexe_autorise' => 'homme', 'frequence_code' => 'NAISSANCE', 'jours_forfait' => 10, 'description' => 'Durée indicative 10 jours', 'limite' => 1, 'limite_frequence_code' => 'NAISSANCE'],
            ['libelle' => 'Congé adoption', 'code' => 'ADOPTION', 'utilise_solde' => false, 'paye' => true, 'frequence_code' => 'NAISSANCE', 'jours_forfait' => 98, 'description' => '10 à 14 semaines selon situation', 'limite' => 1, 'limite_frequence_code' => 'NAISSANCE'],
            ['libelle' => 'Congé sabbatique', 'code' => 'SABBATIQUE', 'utilise_solde' => false, 'paye' => false, 'frequence_code' => 'MANAGER'],
            ['libelle' => 'Congé formation', 'code' => 'FORMATION', 'utilise_solde' => false, 'paye' => true, 'frequence_code' => 'FORMATION'],
        ];

        foreach ($types as $type) {
            $frequenceId = $freqMap[$type['frequence_code']] ?? null;
            $cumulId = isset($type['cumulable_frequence_code']) ? ($freqMap[$type['cumulable_frequence_code']] ?? null) : null;
            $limiteFreqId = isset($type['limite_frequence_code']) ? ($freqMap[$type['limite_frequence_code']] ?? null) : null;
            unset($type['frequence_code'], $type['cumulable_frequence_code'], $type['limite_frequence_code']);
            $payload = array_merge($type, [
                'frequence_id' => $frequenceId,
                'cumulable_frequence_id' => $cumulId,
                'limite_frequence_id' => $limiteFreqId,
            ]);
            TypeConge::updateOrCreate(['code' => $payload['code']], $payload);
        }
    }
}
