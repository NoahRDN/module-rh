<?php

namespace Database\Seeders;

use App\Models\AcquisConge;
use App\Models\Contrat;
use App\Models\Employe;
use App\Models\TypeConge;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestCongeExpirationSeeder extends Seeder
{
    public function run(): void
    {
        $typePaye = TypeConge::where('code', 'PAYE')->first();
        if (!$typePaye) {
            return;
        }

        // // Créer un employé dédié au test d’expiration de congés
        // $emp = Employe::create([
        //     'matricule'      => 'EMP-TEST-EXP',
        //     'nom'            => 'Expire',
        //     'prenom'         => 'Test',
        //     'email'          => 'expire.test@example.com',
        //     'telephone'      => null,
        //     'adresse'        => 'Adresse test',
        //     'date_naissance' => '1990-01-01',
        //     'poste_id'       => 1,
        //     'departement_id' => 1,
        //     'photo'          => null,
        //     'date_embauche'  => '2019-01-01',
        // ]);

        // Contrat::create([
        //     'numero'              => 'CTR-TEST-EXP',
        //     'employe_id'          => $emp->id,
        //     'type_contrat'        => 'CDI',
        //     'date_debut'          => '2019-01-01',
        //     'date_fin'            => null,
        //     'periode_essai_debut' => null,
        //     'periode_essai_fin'   => null,
        //     'renouvelable'        => false,
        //     'salaire_base'        => 500000,
        //     'statut'              => 'en_cours',
        // ]);

        // Un acquis expiré (ne doit pas apparaître dans le solde)
        // $acquisExpire = Carbon::create(2020, 1, 31);
        // AcquisConge::create([
        //     'employe_id'    => $emp->id,
        //     'type_conge_id' => $typePaye->id,
        //     'jours_acquis'  => 2.5,
        //     'acquis_le'     => $acquisExpire->toDateString(),
        //     'expire_le'     => $acquisExpire->copy()->addYears(3)->toDateString(),
        //     'acquis_first'  => $acquisExpire->toDateString(),
        //     'expire_first'  => $acquisExpire->copy()->addYears(3)->toDateString(),
        // ]);

        // // Un acquis toujours valide (apparaît dans le solde)
        // $acquisValide = Carbon::create(2024, 12, 31);
        // AcquisConge::create([
        //     'employe_id'    => $emp->id,
        //     'type_conge_id' => $typePaye->id,
        //     'jours_acquis'  => 2.5,
        //     'acquis_le'     => $acquisValide->toDateString(),
        //     'expire_le'     => $acquisValide->copy()->addYears(3)->toDateString(),
        //     'acquis_first'  => $acquisValide->toDateString(),
        //     'expire_first'  => $acquisValide->copy()->addYears(3)->toDateString(),
        // ]);

        // // Employé supplémentaire pour tester le calcul FIFO d’acquis non expirés
        // $emp2 = Employe::create([
        //     'matricule'      => 'EMP-TEST-FIFO',
        //     'nom'            => 'Fifo',
        //     'prenom'         => 'Demo',
        //     'email'          => 'fifo.demo@example.com',
        //     'telephone'      => null,
        //     'adresse'        => 'Adresse fifo',
        //     'date_naissance' => '1992-05-05',
        //     'poste_id'       => 1,
        //     'departement_id' => 1,
        //     'photo'          => null,
        //     'date_embauche'  => '2023-06-01',
        // ]);

        // Contrat::create([
        //     'numero'              => 'CTR-TEST-FIFO',
        //     'employe_id'          => $emp2->id,
        //     'type_contrat'        => 'CDI',
        //     'date_debut'          => '2023-06-01',
        //     'date_fin'            => null,
        //     'periode_essai_debut' => null,
        //     'periode_essai_fin'   => null,
        //     'renouvelable'        => false,
        //     'salaire_base'        => 600000,
        //     'statut'              => 'en_cours',
        // ]);

        // // Deux acquis non expirés pour vérifier l’affichage du solde
        // $a1 = Carbon::create(2024, 6, 30);
        // $a2 = Carbon::create(2024, 7, 31);
        // AcquisConge::create([
        //     'employe_id'    => $emp2->id,
        //     'type_conge_id' => $typePaye->id,
        //     'jours_acquis'  => 2.5,
        //     'acquis_le'     => $a1->toDateString(),
        //     'expire_le'     => $a1->copy()->addYears(3)->toDateString(),
        //     'acquis_first'  => $a1->toDateString(),
        //     'expire_first'  => $a1->copy()->addYears(3)->toDateString(),
        // ]);
        // AcquisConge::create([
        //     'employe_id'    => $emp2->id,
        //     'type_conge_id' => $typePaye->id,
        //     'jours_acquis'  => 2.5,
        //     'acquis_le'     => $a2->toDateString(),
        //     'expire_le'     => $a2->copy()->addYears(3)->toDateString(),
        //     'acquis_first'  => $a1->toDateString(),
        //     'expire_first'  => $a1->copy()->addYears(3)->toDateString(),
        // ]);
    }
}
