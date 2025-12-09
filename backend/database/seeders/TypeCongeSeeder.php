<?php

namespace Database\Seeders;

use App\Models\TypeConge;
use Illuminate\Database\Seeder;

class TypeCongeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['libelle' => 'Congé payé', 'code' => 'PAYE', 'utilise_solde' => true, 'paye' => true, 'description' => '2,5 jours/mois cumulables 3 ans'],
            ['libelle' => 'Congé sans solde', 'code' => 'SANS_SOLDE', 'utilise_solde' => false, 'paye' => false],
            ['libelle' => 'Congé exceptionnel', 'code' => 'EXC', 'utilise_solde' => false, 'paye' => false],
            ['libelle' => 'Congé maladie', 'code' => 'MALADIE', 'utilise_solde' => false, 'paye' => true, 'justificatif_obligatoire' => true],
            ['libelle' => 'Congé maternité', 'code' => 'MATERNITE', 'utilise_solde' => false, 'paye' => true, 'sexe_autorise' => 'femme'],
            ['libelle' => 'Congé paternité', 'code' => 'PATERNITE', 'utilise_solde' => false, 'paye' => true, 'sexe_autorise' => 'homme'],
            ['libelle' => 'Congé sabbatique', 'code' => 'SABBATIQUE', 'utilise_solde' => false, 'paye' => false],
            ['libelle' => 'Congé formation', 'code' => 'FORMATION', 'utilise_solde' => false, 'paye' => true],
        ];
        foreach ($types as $type) {
            TypeConge::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
