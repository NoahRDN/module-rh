<?php

namespace Database\Seeders;

use App\Models\Contrat;
use App\Models\ContratHistorique;
use Illuminate\Database\Seeder;

class ContratHistoriqueSeeder extends Seeder
{
    public function run(): void
    {
        Contrat::chunk(200, function ($contrats) {
            foreach ($contrats as $contrat) {
                $exists = ContratHistorique::where('contrat_id', $contrat->id)
                    ->where('date_debut', $contrat->date_debut)
                    ->where('date_fin', $contrat->date_fin)
                    ->exists();

                if ($exists) {
                    continue;
                }

                ContratHistorique::create([
                    'contrat_id' => $contrat->id,
                    'numero' => $contrat->numero,
                    'employe_id' => $contrat->employe_id,
                    'type_contrat' => $contrat->type_contrat,
                    'date_debut' => $contrat->date_debut,
                    'date_fin' => $contrat->date_fin,
                    'periode_essai_debut' => $contrat->periode_essai_debut,
                    'periode_essai_fin' => $contrat->periode_essai_fin,
                    'renouvelable' => $contrat->renouvelable,
                    'salaire_base' => $contrat->salaire_base,
                    'statut' => $contrat->statut ?? 'en_cours',
                ]);
            }
        });
    }
}
