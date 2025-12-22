<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoldeConge;
use App\Models\Employe;
use App\Models\TypeConge;
use App\Models\AcquisConge;
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
            $from = $request->query('from');
            $to = $request->query('to');
            $sim = $request->query('simulation_date');
            $matricule = $request->query('matricule');
            $nom = $request->query('nom');
            $type = $request->query('type');
            $departement = $request->query('departement');
            Log::info('Soldes conges index called', [
                'employe_id' => $emp,
                'from' => $from,
                'to' => $to,
                'simulation_date' => $sim,
                'matricule' => $matricule,
                'nom' => $nom,
                'type' => $type,
                'departement' => $departement,
            ]);
            $simulationDate = $sim ? Carbon::parse($sim) : now();

            $query = SoldeConge::with(['employe', 'typeConge'])
                ->orderBy('id', 'desc')
                ->whereHas('employe.contrats', function ($q) use ($simulationDate) {
                    $date = $simulationDate->toDateString();
                    $q->whereDate('date_debut', '<=', $date)
                      ->where(function ($w) use ($date) {
                          $w->whereNull('date_fin')->orWhereDate('date_fin', '>=', $date);
                      });
                })
                ->when($matricule, function ($q) use ($matricule) {
                    $q->whereHas('employe', function ($empQ) use ($matricule) {
                        $empQ->where('matricule', 'ILIKE', '%' . $matricule . '%');
                    });
                })
                ->when($nom, function ($q) use ($nom) {
                    $q->whereHas('employe', function ($empQ) use ($nom) {
                        $empQ->where('nom', 'ILIKE', '%' . $nom . '%')
                             ->orWhere('prenom', 'ILIKE', '%' . $nom . '%');
                    });
                })
                ->when($type, function ($q) use ($type) {
                    $q->whereHas('typeConge', function ($tQ) use ($type) {
                        $tQ->where('libelle', 'ILIKE', '%' . $type . '%')
                           ->orWhere('code', 'ILIKE', '%' . $type . '%');
                    });
                })
                ->when($departement, function ($q) use ($departement) {
                    $q->whereHas('employe.departement', function ($dQ) use ($departement) {
                        $dQ->where('nom', 'ILIKE', '%' . $departement . '%');
                    });
                })
                ->whereDate('expire_first', '>=', $simulationDate->toDateString())
                ->whereDate('premier_acquis', '<=', $simulationDate->toDateString());
            if ($emp) {
                $query->where('employe_id', $emp);
            }
            $page = $query->paginate(10);
            Log::info('Soldes page items', ['items' => collect($page->items())->toArray()]);
            Log::info('Soldes simulation date', ['simulation_date' => $simulationDate->toDateString()]);

            // Recalculer le solde + acquis/utilisé sur la plage (par défaut pivot = simulationDate)
            $fromDate = $from ? Carbon::parse($from) : null;
            $toDate = $to ? Carbon::parse($to) : $simulationDate;
            $pivotDate = $toDate;
            $page->getCollection()->transform(function ($row) use ($fromDate, $toDate, $pivotDate) {
                $resume = $this->congeService->resumeEntre(
                    $row->employe_id,
                    $row->type_conge_id,
                    $fromDate,
                    $toDate
                );

                // Mettre à jour les bornes d'acquisition/expiration sur la période simulée
                $acquisQuery = AcquisConge::where('employe_id', $row->employe_id)
                    ->where('type_conge_id', $row->type_conge_id)
                    ->whereDate('expire_first', '>=', $pivotDate->toDateString())
                    ->whereRaw("(date_trunc('month', acquis_le) + interval '1 month') <= ?", [$pivotDate->toDateString()]);

                if ($fromDate) {
                    $acquisQuery->whereDate('acquis_le', '>=', $fromDate->toDateString());
                }
                if ($toDate) {
                    $acquisQuery->whereDate('acquis_le', '<=', $toDate->toDateString());
                }

                $acquisRange = $acquisQuery->get();
                $premier = $acquisRange->min('acquis_first') ?? $row->acquis_first ?? null;
                $dernier = $acquisRange->max('expire_first') ?? $row->expire_first ?? null;

                $row->premier_acquis = $premier ? \Carbon\Carbon::parse($premier)->toDateString() : null;
                $row->derniere_expiration = $dernier ? \Carbon\Carbon::parse($dernier)->toDateString() : null;
                // Aligner les colonnes principales sur la fenêtre simulée
                $row->acquis_first = $row->premier_acquis;
                $row->expire_first = $row->derniere_expiration;

                $row->acquis_periode = $resume['acquis'];
                $row->utilise_periode = $resume['utilise'];
                $row->solde_periode = $resume['solde'];
                $row->solde_actuel = $resume['solde'];
                $row->solde_annuel = $resume['solde'];
                return $row;
            });

            return response()->json($page);
        } catch (\Throwable $e) {
            Log::error('Erreur liste soldes conges', ['error' => $e->getMessage()]);
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
