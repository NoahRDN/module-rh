<?php

namespace Database\Seeders;

use App\Models\DocumentEmploye;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['contrat', 'bulletin_paie', 'attestation_travail', 'certificat_travail'];

        for ($i = 1; $i <= 10; $i++) {
            $type = $types[($i - 1) % count($types)];

            DocumentEmploye::create([
                'employe_id'      => rand(1, 10),
                'type_document'   => $type,
                'fichier'         => 'documents/' . $type . '_' . $i . '.pdf',
                'date_expiration' => null,
            ]);
        }
    }
}
