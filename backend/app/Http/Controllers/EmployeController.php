<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Models Utilisés
 */
use App\Models\FicheEmploye;
use App\Models\Employe;

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
}
