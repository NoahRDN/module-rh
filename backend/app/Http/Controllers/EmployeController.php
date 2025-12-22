<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Exception;

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
        try {
            $employes = Employe::with(['poste', 'departement'])->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $employes,
                'error' => null
            ]);

        } catch (Exception $e) {
            // On log l'erreur serveur pour le développeur
            Log::error("Erreur lors de la récupération de la liste des employés : " . $e->getMessage());

            // On retourne la réponse JSON d'erreur
            return response()->json([
                'status' => 'error',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function fiche_actuelle($id)
    {
        try {
            $fiche_employe = FicheEmploye::where('id_employe', $id)->first();

            if($fiche_employe === null) {
                return response()->json([
                    'status' => 'error',
                    'data' => null,
                    'error' => "Aucune fiche employé trouvée pour l'ID $id"
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $fiche_employe,
                'error' => null
            ]);

        } catch (Exception $e) {
            Log::error("Erreur fiche actuelle pour l'employé $id : " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function historique_poste_employe($id)
    {
        try {
            // 1. Récupération de l'historique
            $historiques = PosteHistorique::where('id_employe', $id)
                ->with([
                    'poste.unite', 
                    'poste.profil.typeContrat'
                ])
                ->orderBy('date_action', 'desc')
                ->get();

            // 2. Traitement des données
            $resultat = $historiques->map(function ($item) use ($id) {
                
                // Recherche du contrat actif à la date de l'action
                $contrat = ContratEmploye::where('id_employe', $id)
                    ->where('date_debut', '<=', $item->date_action)
                    ->where(function($query) use ($item) {
                        $query->where('date_fin', '>=', $item->date_action)
                              ->orWhereNull('date_fin');
                    })
                    ->with('typeContrat')
                    ->first();

                // Utilisation de l'opérateur nullsafe (?->) pour éviter les crashs si une relation est vide
                return [
                    'action'          => $item->action,
                    'date_historique' => $item->date_action,
                    'description'     => $item->description,

                    'fonction'        => $item->poste->fonction ?? null,
                    'unite'           => $item->poste->unite->nom ?? null,
                    'profil'          => $item->poste->profil->nom ?? null,

                    'contrat_type'    => $contrat ? ($contrat->typeContrat->nom ?? 'Inconnu') : 'Aucun contrat trouvé',
                    'contrat_debut'   => $contrat ? $contrat->date_debut : null,
                    'contrat_fin'     => $contrat ? $contrat->date_fin : null, 
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $resultat,
                'error' => null
            ]);

        } catch (Exception $e) {
            Log::error("Erreur historique poste pour l'employé $id : " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
