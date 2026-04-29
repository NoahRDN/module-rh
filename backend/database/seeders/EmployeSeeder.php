<?php

namespace Database\Seeders;

use App\Models\Employe;
use App\Models\User;
use App\Models\HistoriquePoste;
use Illuminate\Database\Seeder;

class EmployeSeeder extends Seeder
{
    public function run(): void
    {
        $employes = [
            [
                'matricule' => 'EMP-20260429-0001',
                'nom' => 'Rakoto',
                'prenom' => 'Mika',
                'email' => 'mika.rakoto@example.com',
                'telephone' => '+261 34 12 345 67',
                'adresse' => 'Lot II M 24, Antananarivo',
                'date_naissance' => '1991-03-14',
                'poste_id' => 1,
                'departement_id' => 1,
                'num_cnaps' => 'CNAPS-100001',
                'date_embauche' => '2020-02-03',
            ],
            [
                'matricule' => 'EMP-20260429-0002',
                'nom' => 'Rasoanaivo',
                'prenom' => 'Lova',
                'email' => 'lova.rasoanaivo@example.com',
                'telephone' => '+261 32 45 678 90',
                'adresse' => 'Lot VF 12, Fianarantsoa',
                'date_naissance' => '1988-07-22',
                'poste_id' => 2,
                'departement_id' => 2,
                'num_cnaps' => 'CNAPS-100002',
                'date_embauche' => '2019-09-16',
            ],
            [
                'matricule' => 'EMP-20260429-0003',
                'nom' => 'Andriamampianina',
                'prenom' => 'Hery',
                'email' => 'hery.andriamampianina@example.com',
                'telephone' => '+261 33 21 432 10',
                'adresse' => 'Rue du Commerce, Toamasina',
                'date_naissance' => '1995-11-08',
                'poste_id' => 3,
                'departement_id' => 3,
                'num_cnaps' => 'CNAPS-100003',
                'date_embauche' => '2021-06-01',
            ],
            [
                'matricule' => 'EMP-20260429-0004',
                'nom' => 'Randrianarisoa',
                'prenom' => 'Tiana',
                'email' => 'tiana.randrianarisoa@example.com',
                'telephone' => '+261 34 98 765 43',
                'adresse' => 'Lot AB 08, Mahajanga',
                'date_naissance' => '1990-01-30',
                'poste_id' => 4,
                'departement_id' => 4,
                'num_cnaps' => 'CNAPS-100004',
                'date_embauche' => '2018-11-12',
            ],
            [
                'matricule' => 'EMP-20260429-0005',
                'nom' => 'Raveloson',
                'prenom' => 'Soa',
                'email' => 'soa.raveloson@example.com',
                'telephone' => '+261 32 77 654 32',
                'adresse' => 'Lot PK 45, Antsirabe',
                'date_naissance' => '1997-05-19',
                'poste_id' => 5,
                'departement_id' => 5,
                'num_cnaps' => 'CNAPS-100005',
                'date_embauche' => '2022-04-18',
            ],
            [
                'matricule' => 'EMP-20260429-0006',
                'nom' => 'Rajaonarivelo',
                'prenom' => 'Nirina',
                'email' => 'nirina.rajaonarivelo@example.com',
                'telephone' => '+261 33 65 123 87',
                'adresse' => 'Lot IT 17, Toliara',
                'date_naissance' => '1986-09-04',
                'poste_id' => 6,
                'departement_id' => 1,
                'num_cnaps' => 'CNAPS-100006',
                'date_embauche' => '2017-08-07',
            ],
            [
                'matricule' => 'EMP-20260429-0007',
                'nom' => 'Rakotomalala',
                'prenom' => 'Fanja',
                'email' => 'fanja.rakotomalala@example.com',
                'telephone' => '+261 34 56 210 98',
                'adresse' => 'Avenue de France, Antsiranana',
                'date_naissance' => '1993-12-11',
                'poste_id' => 7,
                'departement_id' => 2,
                'num_cnaps' => 'CNAPS-100007',
                'date_embauche' => '2023-01-23',
            ],
            [
                'matricule' => 'EMP-20260429-0008',
                'nom' => 'Razafindrakoto',
                'prenom' => 'Toky',
                'email' => 'toky.razafindrakoto@example.com',
                'telephone' => '+261 32 14 258 36',
                'adresse' => 'Lot MA 31, Morondava',
                'date_naissance' => '1999-02-27',
                'poste_id' => 8,
                'departement_id' => 3,
                'num_cnaps' => 'CNAPS-100008',
                'date_embauche' => '2024-03-04',
            ],
            [
                'matricule' => 'EMP-20260429-0009',
                'nom' => 'Andrianjafy',
                'prenom' => 'Mamy',
                'email' => 'mamy.andrianjafy@example.com',
                'telephone' => '+261 33 87 654 21',
                'adresse' => 'Lot BZ 09, Ambositra',
                'date_naissance' => '1992-06-06',
                'poste_id' => 1,
                'departement_id' => 4,
                'num_cnaps' => 'CNAPS-100009',
                'date_embauche' => '2020-10-05',
            ],
            [
                'matricule' => 'EMP-20260429-0010',
                'nom' => 'Ramanantsoa',
                'prenom' => 'Hanitra',
                'email' => 'hanitra.ramanantsoa@example.com',
                'telephone' => '+261 34 43 210 65',
                'adresse' => 'Lot AN 52, Manakara',
                'date_naissance' => '1989-10-16',
                'poste_id' => 2,
                'departement_id' => 5,
                'num_cnaps' => 'CNAPS-100010',
                'date_embauche' => '2019-05-20',
            ],
        ];

        foreach ($employes as $data) {
            $email = $data['email'];
            $employe = Employe::create([
                ...$data,
                'photo' => null,
            ]);

            // Créer un utilisateur lié
            User::create([
                'name' => $employe->nom . ' ' . $employe->prenom,
                'email' => $email,
                'password' => env('DEFAULT_USER_PASSWORD', 'password'),
                'role' => 'employe',
                'employe_id' => $employe->id,
            ]);

            // Historique de poste initial
            HistoriquePoste::create([
                'employe_id' => $employe->id,
                'poste_id' => $employe->poste_id,
                'departement_id' => $employe->departement_id,
                'date_changement' => $employe->date_embauche,
                'motif' => 'Affectation initiale',
            ]);
        }
    }
}
