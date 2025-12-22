<?php

namespace Database\Seeders;

use App\Models\DocumentEmploye;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            DocumentEmploye::create([
                'employe_id'      => rand(1, 10),
                'type_document'   => 'CIN',
                'fichier'         => 'documents/cin_' . $i . '.pdf',
                'date_expiration' => null,
            ]);
        }
    }
}
