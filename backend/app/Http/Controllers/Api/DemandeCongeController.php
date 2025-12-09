<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DemandeCongeRequest;
use App\Models\DemandeConge;
use App\Models\TypeConge;
use App\Services\CongeService;
use App\Models\CalendrierEvenement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DemandeCongeController extends Controller
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
            $query = DemandeConge::with(['employe', 'typeConge', 'approbateur'])->orderBy('created_at', 'desc');
            if ($emp) {
                $query->where('employe_id', $emp);
            }
            if ($from) {
                $query->whereDate('date_debut', '>=', $from);
            }
            if ($to) {
                $query->whereDate('date_fin', '<=', $to);
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
            $data = $request->validated();
            if (empty($data['type_conge_id'])) {
                $data['type_conge_id'] = TypeConge::where('code', CongeService::CODE_CONGE_PAYE)->value('id');
            }
            if (empty($data['jours_demandes']) && !empty($data['date_debut']) && !empty($data['date_fin'])) {
                $debut = Carbon::parse($data['date_debut']);
                $fin = Carbon::parse($data['date_fin']);
                $data['jours_demandes'] = $debut->diffInDays($fin) + 1;
            }
            $demande = DemandeConge::create($data);
            return response()->json($demande, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation demande conge', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return DemandeConge::with(['employe', 'typeConge', 'approbateur'])->findOrFail($id);
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
            $demande = DemandeConge::with(['type', 'typeConge'])->findOrFail($id);

            $demande->update([
                'statut' => 'rh_valide',
                'approuve_par' => $request->user()->id ?? null,
            ]);

            $this->congeService->consommerDemande($demande);

            // création événement calendrier
            CalendrierEvenement::create([
                'type'        => 'conge',
                'employe_id'  => $demande->employe_id,
                'date_debut'  => $demande->date_debut,
                'date_fin'    => $demande->date_fin,
                'description' => $demande->typeConge?->libelle ?? 'Congé',
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
