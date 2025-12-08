<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContratRequest;
use App\Models\Contrat;
use App\Http\Controllers\Api\SoldeCongeController;
use App\Models\ContratHistorique;
use App\Models\Employe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ContratController extends Controller
{
    public function index(Request $request)
    {
        try {
            $employe = $request->query('employe_id');

            $query = Contrat::with(['employe.departement', 'employe.poste'])->orderBy('date_debut', 'desc');

            if ($employe) {
                $query->where('employe_id', $employe);
            }

            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste contrats', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(ContratRequest $request)
    {
        try {
            $data = $this->computeDates($request->validated());
            if (empty($data['numero'])) {
                $data['numero'] = $this->genererNumero();
            }
            $data['statut'] = $data['statut'] ?? 'en_cours';

            $this->cloreContratsActifs($data['employe_id'] ?? $request->employe_id, $data['date_debut'] ?? null);

            $contrat = Contrat::create($data);

            // Créditer les soldes de congés à la création d'un contrat
            try {
                app(SoldeCongeController::class)->accrue();
            } catch (\Throwable $e) {
                Log::warning('Accrual soldes après création contrat a échoué', ['error' => $e->getMessage()]);
            }
            $this->historiser($contrat);
            return response()->json($contrat, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation contrat', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return Contrat::with(['employe.departement', 'employe.poste'])->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show contrat', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(ContratRequest $request, $id)
    {
        try {
            $contrat = Contrat::findOrFail($id);
            $data = $request->validated();
            $data = $this->computeDates($data, $contrat);
            if (empty($data['numero'])) {
                unset($data['numero']);
            }
            $contrat->update($data);
            // on enregistre la nouvelle version dans l'historique pour tracer chaque renouvellement/modification
            $this->historiser($contrat);

            return response()->json($contrat);
        } catch (\Throwable $e) {
            Log::error('Erreur update contrat', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Contrat::findOrFail($id)->delete();
            return response()->json(['message' => 'Contrat supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression contrat', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    protected function genererNumero(): string
    {
        $prefix = 'CTR';
        $date = now()->format('Ymd');
        do {
            $rand = mt_rand(1000, 9999);
            $num = "$prefix-$date-$rand";
        } while (Contrat::where('numero', $num)->exists());

        return $num;
    }

    /**
     * Calcule les dates de fin sur base des durées fournies.
     */
    protected function computeDates(array $data, ?Contrat $contrat = null): array
    {
        $debut = isset($data['date_debut']) ? \Carbon\Carbon::parse($data['date_debut']) : null;

        if ($debut && empty($data['date_fin'])) {
            $dateFin = $debut->copy();
            $added = false;
            if (!empty($data['duree_ans'])) {
                $dateFin->addYears($data['duree_ans']);
                $added = true;
            }
            if (!empty($data['duree_mois'])) {
                $dateFin->addMonths($data['duree_mois']);
                $added = true;
            }
            if (!empty($data['duree_jours'])) {
                $dateFin->addDays($data['duree_jours']);
                $added = true;
            }
            if ($added) {
                $data['date_fin'] = $dateFin->toDateString();
            }
        }

        // période d'essai
        $essaiDebut = $data['periode_essai_debut'] ?? $data['essai_debut'] ?? ($this->hasEssaiDuration($data) ? $debut?->toDateString() : null);
        if ($essaiDebut && $this->hasEssaiDuration($data) && empty($data['periode_essai_fin'])) {
            $data['periode_essai_debut'] = $essaiDebut;
            $essaiStart = Carbon::parse($essaiDebut);
            if (!empty($data['essai_ans'])) {
                $essaiStart->addYears($data['essai_ans']);
            }
            if (!empty($data['essai_mois'])) {
                $essaiStart->addMonths($data['essai_mois']);
            }
            if (!empty($data['essai_jours'])) {
                $essaiStart->addDays($data['essai_jours']);
            }
            $data['periode_essai_fin'] = $essaiStart->toDateString();
        }

        // nettoyage des champs de durée (non stockés)
        unset($data['duree_jours'], $data['duree_mois'], $data['duree_ans'], $data['essai_jours'], $data['essai_mois'], $data['essai_ans'], $data['essai_debut']);

        return $data;
    }

    protected function hasEssaiDuration(array $data): bool
    {
        return !empty($data['essai_jours']) || !empty($data['essai_mois']) || !empty($data['essai_ans']);
    }

    protected function cloreContratsActifs(int $employeId, ?string $newStart = null): void
    {
        $start = $newStart ? Carbon::parse($newStart)->toDateString() : now()->toDateString();
        $contratsActifs = Contrat::where('employe_id', $employeId)
            ->whereDate('date_debut', '<=', $start)
            ->where(function ($q) use ($start) {
                $q->whereNull('date_fin')->orWhereDate('date_fin', '>=', $start);
            })
            ->get();

        foreach ($contratsActifs as $c) {
            $fin = $c->date_fin;
            if (!$fin || $fin->toDateString() >= $start) {
                $c->date_fin = Carbon::parse($start)->subDay()->toDateString();
            }
            $c->statut = 'termine';
            $c->save();
            $this->historiser($c);
        }
    }

    protected function historiser(Contrat $contrat): void
    {
        ContratHistorique::create([
            'contrat_id' => $contrat->id,
            'numero' => $contrat->numero,
            'employe_id' => $contrat->employe_id,
            'type_contrat' => $contrat->type_contrat,
            'date_debut' => $contrat->date_debut,
            'date_fin' => $contrat->date_fin,
            'periode_essai_debut' => $contrat->periode_essai_debut,
            'periode_essai_fin' => $contrat->periode_essai_fin,
            'renouvelable' => $contrat->renouvelable,
            'salaire_base' => $contrat->salaire_base,
            'statut' => $contrat->statut ?? 'en_cours',
        ]);
    }
}
