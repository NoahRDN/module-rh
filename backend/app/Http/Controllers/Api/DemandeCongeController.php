<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DemandeCongeRequest;
use App\Models\AbsenceType;
use App\Models\DemandeConge;
use App\Models\SoldeConge;
use App\Models\CalendrierEvenement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DemandeCongeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $emp = $request->query('employe_id');
            $query = DemandeConge::with(['employe', 'type', 'approbateur'])->orderBy('created_at', 'desc');
            if ($emp) {
                $query->where('employe_id', $emp);
            }
            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste demandes conge', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(DemandeCongeRequest $request)
    {
        try {
            $demande = DemandeConge::create($request->validated());
            return response()->json($demande, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation demande conge', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return DemandeConge::with(['employe', 'type', 'approbateur'])->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show demande conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(DemandeCongeRequest $request, $id)
    {
        try {
            $demande = DemandeConge::findOrFail($id);
            $demande->update($request->validated());
            return response()->json($demande);
        } catch (\Throwable $e) {
            Log::error('Erreur update demande conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DemandeConge::findOrFail($id)->delete();
            return response()->json(['message' => 'Demande supprimée']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression demande conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function approveByManager(Request $request, $id)
    {
        try {
            $demande = DemandeConge::findOrFail($id);
            $demande->update([
                'statut' => 'manager_valide',
                'approuve_par' => $request->user()->id ?? null,
            ]);
            return response()->json(['message' => 'Validé par manager', 'demande' => $demande]);
        } catch (\Throwable $e) {
            Log::error('Erreur approbation manager demande conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function approveByRH(Request $request, $id)
    {
        try {
            $demande = DemandeConge::with(['type'])->findOrFail($id);
            $type = $demande->type;

            $debut = Carbon::parse($demande->date_debut);
            $fin = Carbon::parse($demande->date_fin);
            $jours = $debut->diffInDays($fin) + 1;

            if ($type && $type->est_payant) {
                $defaultSolde = $type->jours_annuels ?? 0;

                $solde = SoldeConge::firstOrCreate(
                    ['employe_id' => $demande->employe_id, 'type_id' => $demande->type_id],
                    ['solde_actuel' => $defaultSolde, 'solde_annuel' => $defaultSolde]
                );

                if ($solde->solde_actuel < $jours) {
                    return response()->json(['message' => 'Solde insuffisant'], 422);
                }

                $solde->decrement('solde_actuel', $jours);
            }

            $demande->update([
                'statut' => 'rh_valide',
                'approuve_par' => $request->user()->id ?? null,
            ]);

            // création événement calendrier
            CalendrierEvenement::create([
                'type'        => 'conge',
                'employe_id'  => $demande->employe_id,
                'date_debut'  => $demande->date_debut,
                'date_fin'    => $demande->date_fin,
                'description' => $type ? $type->nom : 'Congé',
            ]);

            return response()->json(['message' => 'Validé par RH', 'demande' => $demande]);
        } catch (\Throwable $e) {
            Log::error('Erreur validation RH demande conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $demande = DemandeConge::findOrFail($id);
            $demande->update([
                'statut' => 'rejete',
                'approuve_par' => $request->user()->id ?? null,
            ]);
            return response()->json(['message' => 'Demande rejetée', 'demande' => $demande]);
        } catch (\Throwable $e) {
            Log::error('Erreur rejet demande conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
