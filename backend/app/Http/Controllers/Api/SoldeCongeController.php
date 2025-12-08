<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SoldeCongeRequest;
use App\Models\SoldeConge;
use App\Models\Employe;
use App\Models\AbsenceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SoldeCongeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $emp = $request->query('employe_id');
            $query = SoldeConge::with(['employe', 'type'])
                ->orderBy('id', 'desc')
                ->whereHas('employe.contrats', function ($q) {
                    $now = now()->toDateString();
                    $q->whereDate('date_debut', '<=', $now)
                      ->where(function ($w) use ($now) {
                          $w->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
                      });
                });
            if ($emp) {
                $query->where('employe_id', $emp);
            }
            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste soldes conges', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(SoldeCongeRequest $request)
    {
        try {
            $solde = SoldeConge::create($request->validated());
            return response()->json($solde, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation solde conge', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return SoldeConge::with(['employe', 'type'])->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show solde conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(SoldeCongeRequest $request, $id)
    {
        try {
            $solde = SoldeConge::findOrFail($id);
            $solde->update($request->validated());
            return response()->json($solde);
        } catch (\Throwable $e) {
            Log::error('Erreur update solde conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            SoldeConge::findOrFail($id)->delete();
            return response()->json(['message' => 'Solde supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression solde conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Créditer automatiquement les congés payés : 2,5 jours/mois cumulables sur 3 ans (max 90 jours).
     */
    public function accrue()
    {
        try {
            $typePayes = AbsenceType::where('est_payant', true)->get();
            $employes = Employe::all();

            foreach ($employes as $emp) {
                $embauche = $emp->date_embauche ? Carbon::parse($emp->date_embauche) : null;
                if (!$embauche) {
                    continue;
                }
                $months = min($embauche->diffInMonths(Carbon::now()), 36); // limite 3 ans

                foreach ($typePayes as $type) {
                    $baseMin = $type->jours_annuels ?: 90; // si non renseigné, plafond direct
                    $entitlement = min(max($months * 2.5, $baseMin), 90); // minimum baseMin, maxi 90
                    
                    $solde = SoldeConge::firstOrCreate(
                        ['employe_id' => $emp->id, 'type_id' => $type->id],
                        ['solde_actuel' => $entitlement, 'solde_annuel' => $entitlement]
                    );

                    $diff = $entitlement - $solde->solde_annuel;
                    if ($diff > 0) {
                        $solde->solde_annuel = $entitlement;
                        $solde->solde_actuel += $diff;
                        $solde->save();
                    }
                }
            }

            return response()->json(['message' => 'Soldes mis à jour (2,5 j/mois, max 3 ans)']);
        } catch (\Throwable $e) {
            Log::error('Erreur accrual soldes conges', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
