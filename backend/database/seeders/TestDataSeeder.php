<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Users (idempotent)
        DB::table('users')->updateOrInsert(
            ['email' => 'rh.admin@example.com'],
            [
                'name' => 'RH Admin',
                // 'password' => Hash::make('demo-pass'),
                'password' => 'demo-pass',
                'role' => 'rh',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'paie.manager@example.com'],
            [
                'name' => 'Paie Manager',
                // 'password' => Hash::make('demo-pass'),
                'password' => 'demo-pass',
                'role' => 'paie',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Employés
        // Employés (idempotent by matricule)
        DB::table('employes')->updateOrInsert(
            ['matricule' => 'EMP-0001'],
            [
                'id' => 1001,
                'prenom' => 'Jean',
                'nom' => 'Martin',
                'email' => 'jean.martin@example.com',
                'date_naissance' => '1985-06-12',
                'date_embauche' => '2019-03-01',
                'poste_id' => null,
                'departement_id' => null,
                'num_cnaps' => null,
                'photo' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('employes')->updateOrInsert(
            ['matricule' => 'EMP-0002'],
            [
                'id' => 1002,
                'prenom' => 'Alice',
                'nom' => 'Dupont',
                'email' => 'alice.dupont@example.com',
                'date_naissance' => '1990-11-02',
                'date_embauche' => '2020-07-15',
                'poste_id' => null,
                'departement_id' => null,
                'num_cnaps' => null,
                'photo' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Link users to employees (if column exists)
        try {
            DB::table('users')->where('email', 'rh.admin@example.com')->update(['employe_id' => 1001]);
            DB::table('users')->where('email', 'paie.manager@example.com')->update(['employe_id' => 1002]);
        } catch (\Throwable $e) {
            // ignore if column does not exist yet
        }

        // Documents employés
        // Documents employés (idempotent by fichier)
        DB::table('documents_employes')->updateOrInsert(
            ['fichier' => 'documents/Contrat_CDI_Jean_Martin.pdf'],
            [
                'id' => 5001,
                'employe_id' => 1001,
                'group_uuid' => null,
                'type_document' => 'contrat',
                'date_expiration' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('documents_employes')->updateOrInsert(
            ['fichier' => 'documents/Bulletin_2024-03_Jean_Martin.pdf'],
            [
                'id' => 5002,
                'employe_id' => 1001,
                'group_uuid' => null,
                'type_document' => 'bulletin_paie',
                'date_expiration' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Caisses: ensure main caisse exists and has the demo solde
        try {
            DB::table('caisses')->updateOrInsert(
                ['nom' => 'Caisse principale'],
                [
                    'description' => 'Caisse utilisée pour paie',
                    'solde' => 100000.00,
                    'active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // ignore if table missing in minimal schemas
        }

        // Mouvements caisse
        try {
            DB::table('caisse_mouvements')->updateOrInsert(
                ['id' => 9001],
                [
                    'caisse_id' => 1,
                    'paie_id' => null,
                    'type' => 'debit',
                    'montant' => -6400.00,
                    'source' => 'PAIE-2024-04',
                    'description' => 'Paiement salaires avril 2024',
                    'statut' => 'valide',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // ignore if table missing
        }

        // Paie synthèse mensuelle
        try {
            DB::table('paie_synthese_mensuelle')->updateOrInsert(
                ['mois' => '2024-04', 'employe_id' => 1001],
                [
                    'statut' => 'termine',
                    'employe_matricule' => 'EMP-0001',
                    'employe_nom' => 'Martin',
                    'employe_prenom' => 'Jean',
                    'salaire_base' => 3200.00,
                    'total_brut' => 3200.00,
                    'net_a_payer' => 3200.00,
                    'generated_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // ignore if table missing
        }
    }
}
