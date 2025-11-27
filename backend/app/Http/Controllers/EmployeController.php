<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Models Utilisés
 */
use App\Models\Employe;
use App\Models\FicheEmploye;
use App\Models\PosteHistorique;
use App\Models\ContratEmploye;

class EmployeController extends Controller
{
    public function liste_employe()
    {
        $employes = Employe::with(['personne', 'poste'])->get();
        return response()->json($employes);
    }

    public function fiche_actuelle($id)
    {
        $fiche_employe = FicheEmploye::where('id_employe', 1)->first();
        return response()->json($fiche_employe);
    }

    public function historique_poste_employe($id)
    {
        // 1. Récupération de l'historique avec les relations (noms en minuscules)
        $historiques = PosteHistorique::where('id_employe', $id)
            ->with([
                'poste.unite', 
                'poste.profil.typeContrat'
            ])
            ->orderBy('date_action', 'desc') // Ordre chronologique inverse
            ->get();

        // 2. Traitement des données
        $resultat = $historiques->map(function ($item) use ($id) {
            
            // On cherche le contrat qui était ACTIF à la date de l'action ('date_action')
            // Logique : Le contrat a commencé AVANT l'action, et s'est terminé APRES l'action (ou n'est pas fini)
            $contrat = ContratEmploye::where('id_employe', $id)
                ->where('date_debut', '<=', $item->date_action)
                ->where(function($query) use ($item) {
                    $query->where('date_fin', '>=', $item->date_action)
                          ->orWhereNull('date_fin'); // Cas du CDI actuel
                })
                ->with('typeContrat')
                ->first();

            return [
                // Infos sur l'événement (changement de poste)
                'action'          => $item->action,
                'date_historique' => $item->date_action,
                'description'     => $item->description,

                // Infos sur le Poste à ce moment-là
                'fonction'        => $item->poste->fonction ?? null,
                'unite'           => $item->poste->unite->nom ?? null,
                'profil'          => $item->poste->profil->nom ?? null,

                // Infos sur le Contrat lié à cette période
                // Si c'est un vieux contrat (ex: CDD de 2022), date_fin sera ex: '2022-12-31'
                // Si c'est le contrat actuel (ex: CDI), date_fin sera null
                'contrat_type'    => $contrat ? ($contrat->typeContrat->nom ?? 'Inconnu') : 'Aucun contrat trouvé',
                'contrat_debut'   => $contrat ? $contrat->date_debut : null,
                'contrat_fin'     => $contrat ? $contrat->date_fin : null, 
            ];
        });

        return response()->json($resultat);
    }
}
