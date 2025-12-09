<?php

namespace Database\Seeders;

use App\Models\AbsenceType;
use Illuminate\Database\Seeder;

class AbsenceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Congé payé', 'description' => 'Congé annuel rémunéré', 'est_payant' => true, 'jours_annuels' => 20],
            ['nom' => 'Congé maladie', 'description' => 'Absence pour maladie', 'est_payant' => true, 'jours_annuels' => null],
            ['nom' => 'Congé exceptionnel', 'description' => 'Motif familial ou autre', 'est_payant' => true, 'jours_annuels' => 5],
            ['nom' => 'Sans solde', 'description' => 'Congé non rémunéré', 'est_payant' => false, 'jours_annuels' => null],
        ];

        AbsenceType::insert($types);
    }
}
