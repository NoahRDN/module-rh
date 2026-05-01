<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DemandeCongeRequest;
use App\Models\DemandeConge;
use App\Models\TypeConge;
use App\Services\CongeService;
use App\Models\CalendrierEvenement;
use App\Models\DocumentEmploye;
use App\Models\FrequenceConge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            $perPage = min(100, max(1, (int) $request->query('per_page', 10)));
            $query = DemandeConge::query()
                ->select([
                    'id',
                    'employe_id',
                    'type_conge_id',
                    'jours_demandes',
                    'date_debut',
                    'date_fin',
                    'statut',
                    'motif',
                    'approuve_par',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'employe:id,matricule,nom,prenom,poste_id,departement_id',
                    'typeConge:id,code,libelle',
                    'approbateur:id,name,email,role',
                ])
                ->orderBy('created_at', 'desc');
            if ($emp) {
                $query->where('employe_id', $emp);
            }
            if ($from) {
                $query->whereDate('date_debut', '>=', $from);
            }
            if ($to) {
                $query->whereDate('date_fin', '<=', $to);
            }
            return response()->json($query->paginate($perPage));
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
            // Calcul automatique de la durée et de la date de fin selon le type de congé
            $type = TypeConge::find($data['type_conge_id']);
            if (!empty($data['date_debut']) && $type) {
                $debut = Carbon::parse($data['date_debut']);
                $joursForfait = $type->jours_forfait ?? null;

                // Besoin de saisir une date de fin dès lors que le congé utilise un solde
                // ou qu'il n'a pas de forfait prédéfini (durée flexible).
                $withDateFin = $type->utilise_solde || is_null($joursForfait);

                if ($withDateFin && !empty($data['date_fin'])) {
                    $fin = Carbon::parse($data['date_fin']);
                    $jours = $debut->diffInDays($fin) + 1;
                } elseif (!empty($data['jours_demandes'])) {
                    $jours = (float) $data['jours_demandes'];
                } elseif ($joursForfait) {
                    $jours = (float) $joursForfait;
                } else {
                    $jours = 1;
                }

                $data['jours_demandes'] = $jours;
                $data['date_fin'] = $withDateFin
                    ? ($data['date_fin'] ?? $debut->copy()->addDays(max(0, $jours - 1))->toDateString())
                    : $debut->copy()->addDays(max(0, $jours - 1))->toDateString();
            }

            // Vérifier la limite par type/frequence (ex: 1 mariage/an, X par mois...)
            if ($type && $type->limite && $type->limite_frequence_id) {
                $this->verifierLimite($data, $type);
            }

            $demande = DemandeConge::create($data);

            // Sauvegarde du justificatif éventuel dans les documents employés
            if ($request->hasFile('justificatif')) {
                $path = $request->file('justificatif')->store('documents/conges', 'public');
                DocumentEmploye::create([
                    'employe_id'     => $demande->employe_id,
                    'type_document'  => $request->input('type_document') ?: 'Justificatif congé',
                    'fichier'        => $path,
                    'date_expiration'=> null,
                ]);
            }

            return response()->json($demande, 201);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (!$msg && $e instanceof HttpResponseException) {
                $msg = $e->getResponse()?->getContent();
            }
            Log::error('Erreur creation demande conge', ['error' => $msg]);
            if ($e instanceof HttpResponseException) {
                throw $e; // laisser passer la réponse 422 déjà formatée
            }
            if ($e instanceof ValidationException) {
                return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            }
            return response()->json(['message' => $msg ?: 'Erreur serveur'], 500);
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
            $demande = DemandeConge::with(['typeConge'])->findOrFail($id);

            // Validation unique : le manager finalise et consomme directement
            $demande->update([
                'statut' => 'rh_valide',
                'approuve_par' => $request->user()->id ?? null,
            ]);

            // Consommer le solde et créer l'événement calendrier (comme la validation RH auparavant)
            $this->congeService->consommerDemande($demande);

            CalendrierEvenement::create([
                'type'        => 'conge',
                'employe_id'  => $demande->employe_id,
                'date_debut'  => $demande->date_debut,
                'date_fin'    => $demande->date_fin,
                'description' => $demande->typeConge?->libelle ?? 'Congé',
                'meta'        => [
                    'type_conge_id'      => $demande->type_conge_id,
                    'type_conge_code'    => $demande->typeConge?->code,
                    'type_conge_libelle' => $demande->typeConge?->libelle,
                    'demande_id'         => $demande->id,
                ],
            ]);

            return response()->json(['message' => 'Validé par manager', 'demande' => $demande]);
        } catch (\Throwable $e) {
            Log::error('Erreur approbation manager demande conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function approveByRH(Request $request, $id)
    {
        // Validation RH n'est plus utilisée (validation unique manager)
        return response()->json(['message' => 'Validation RH désactivée'], 200);
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

    /**
     * Vérifie la limite du type de congé sur une période donnée (ex: 1/an, 1/mois, 1/événement).
     */
    protected function verifierLimite(array $data, TypeConge $type): void
    {
        $freq = $type->limiteFrequence;
        if (!$freq || !$type->limite) {
            return;
        }
        $debut = Carbon::parse($data['date_debut']);
        $start = $debut->copy();
        $end = $debut->copy();
        switch ($freq->code) {
            case 'MOIS':
                $start = $debut->copy()->startOfMonth();
                $end = $debut->copy()->endOfMonth();
                break;
            case 'AN':
                $start = $debut->copy()->startOfYear();
                $end = $debut->copy()->endOfYear();
                break;
            case 'EVENEMENT':
                // on considère toute la vie de l'employé pour l'événement (mariage, décès...)
                $start = Carbon::parse('1900-01-01');
                $end = Carbon::parse('2999-12-31');
                break;
            default:
                // fallback: limite sur l'année
                $start = $debut->copy()->startOfYear();
                $end = $debut->copy()->endOfYear();
                break;
        }

        $count = DemandeConge::where('employe_id', $data['employe_id'])
            ->where('type_conge_id', $type->id)
            ->whereIn('statut', ['en_attente', 'manager_valide', 'rh_valide'])
            ->whereDate('date_debut', '>=', $start->toDateString())
            ->whereDate('date_debut', '<=', $end->toDateString())
            ->count();

        if ($count >= $type->limite) {
            // Log::info('Limite congé atteinte', [
            //     'employe_id' => $data['employe_id'],
            //     'type_conge_id' => $type->id,
            //     'limite' => $type->limite,
            //     'frequence' => $freq->libelle ?? $freq->code,
            // ]);
            abort(response()->json([
                'message' => 'Limite atteinte pour ce type de congé sur la période',
                'limite' => $type->limite,
                'frequence' => $freq->libelle ?? $freq->code,
            ], 422));
        }
    }
}
