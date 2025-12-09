<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SoldeCongeRequest;
use App\Models\SoldeConge;
use App\Models\Employe;
use App\Models\TypeConge;
use App\Services\CongeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SoldeCongeController extends Controller
{
    public function __construct(private CongeService $congeService)
    {
    }

    public function index(Request $request)
    {
        try {
            $emp = $request->query('employe_id');
            $query = SoldeConge::with(['employe', 'typeConge'])
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
            return SoldeConge::with(['employe', 'typeConge'])->findOrFail($id);
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
     * Créditer automatiquement les congés payés via le service métier.
     */
    public function accrue()
    {
        try {
            $typeConge = TypeConge::where('code', CongeService::CODE_CONGE_PAYE)->first();
            if (!$typeConge) {
                return response()->json(['message' => 'Type congé PAYE introuvable'], 422);
            }
            Employe::chunk(100, function ($chunk) {
                foreach ($chunk as $emp) {
                    $this->congeService->accrueMensuelPourEmploye($emp);
                    }
            });

            return response()->json(['message' => 'Soldes mis à jour via CongeService']);
        } catch (\Throwable $e) {
            Log::error('Erreur accrual soldes conges', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function soldePeriode(Request $request)
    {
        $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'type_conge_id' => 'nullable|exists:types_conges,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $typeId = $request->type_conge_id ?: TypeConge::where('code', CongeService::CODE_CONGE_PAYE)->value('id');
        if (!$typeId) {
            return response()->json(['message' => 'Type de congé introuvable'], 422);
        }

        $from = $request->filled('from') ? Carbon::parse($request->from) : null;
        $to = $request->filled('to') ? Carbon::parse($request->to) : null;

        $solde = $this->congeService->soldeEntre($request->employe_id, $typeId, $from, $to);

        return response()->json([
            'employe_id' => $request->employe_id,
            'type_conge_id' => $typeId,
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'solde' => $solde,
        ]);
    }
}
