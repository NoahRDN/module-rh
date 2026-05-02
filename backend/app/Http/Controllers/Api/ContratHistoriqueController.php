<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratHistorique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContratHistoriqueController extends Controller
{
    public function index(Request $request)
    {
        try {
            $employe = $request->query('employe_id');
            $contrat = $request->query('contrat_id');

            $query = ContratHistorique::with(['employe', 'contrat'])->orderByDesc('created_at');

            if ($contrat) {
                $query->where('contrat_id', $contrat);
            } elseif ($employe) {
                $query->where('employe_id', $employe);
            }
            if ($request->filled('numero')) {
                $term = $request->query('numero');
                $query->whereHas('contrat', fn ($q) => $q->where('numero', 'ILIKE', "%{$term}%"));
            }
            if ($request->filled('matricule')) {
                $term = $request->query('matricule');
                $query->whereHas('employe', fn ($q) => $q->where('matricule', 'ILIKE', "%{$term}%"));
            }
            if ($request->filled('nom')) {
                $term = $request->query('nom');
                $query->whereHas('employe', fn ($q) => $q->where('nom', 'ILIKE', "%{$term}%")->orWhere('prenom', 'ILIKE', "%{$term}%"));
            }
            if ($request->filled('type')) {
                $query->where('type_contrat', 'ILIKE', '%' . $request->query('type') . '%');
            }
            if ($request->filled('departement')) {
                $term = $request->query('departement');
                $query->whereHas('employe.departement', fn ($q) => $q->where('nom', 'ILIKE', "%{$term}%"));
            }
            if ($request->filled('poste')) {
                $term = $request->query('poste');
                $query->whereHas('employe.poste', fn ($q) => $q->where('nom', 'ILIKE', "%{$term}%"));
            }
            if ($request->filled('from')) {
                $query->whereDate('date_debut', '>=', $request->query('from'));
            }
            if ($request->filled('to')) {
                $query->whereDate('date_fin', '<=', $request->query('to'));
            }
            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste historique contrats', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
