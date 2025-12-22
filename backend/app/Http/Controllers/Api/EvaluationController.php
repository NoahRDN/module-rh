<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CritereEvaluation;
use App\Models\Evaluation;
use App\Models\EvaluationDetail;
use App\Models\Employe;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    /**
     * Liste des évaluations avec filtres
     */
    public function index(Request $request)
    {
        $query = Evaluation::with(['employe.poste', 'employe.departement', 'evaluateur', 'details.critere']);

        // Filtre par période
        if ($request->has('periode')) {
            $query->where('periode', $request->periode);
        }

        // Filtre par année
        if ($request->has('annee')) {
            $query->where('periode', 'like', $request->annee . '%');
        }

        // Filtre par employé
        if ($request->has('employe_id')) {
            $query->where('employe_id', $request->employe_id);
        }

        // Filtre par département
        if ($request->has('departement_id')) {
            $query->whereHas('employe', function ($q) use ($request) {
                $q->where('departement_id', $request->departement_id);
            });
        }

        // Filtre par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $evaluations = $query->orderByDesc('date_evaluation')
            ->orderBy('periode')
            ->paginate($request->get('per_page', 20));

        return response()->json($evaluations);
    }

    /**
     * Affiche une évaluation
     */
    public function show($id)
    {
        $evaluation = Evaluation::with([
            'employe.poste',
            'employe.departement',
            'evaluateur',
            'details.critere'
        ])->findOrFail($id);

        return response()->json(['data' => $evaluation]);
    }

    /**
     * Crée une nouvelle évaluation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'periode' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'notes' => 'required|array',
            'notes.*.critere_id' => 'required|exists:criteres_evaluation,id',
            'notes.*.note' => 'required|numeric|min:0|max:100',
            'notes.*.commentaire' => 'nullable|string',
            'points_forts' => 'nullable|string',
            'axes_amelioration' => 'nullable|string',
            'objectifs' => 'nullable|string',
            'commentaire_general' => 'nullable|string',
            'statut' => 'sometimes|in:brouillon,valide',
        ]);

        // Vérifier si une évaluation existe déjà pour cet employé et cette période
        $exists = Evaluation::where('employe_id', $validated['employe_id'])
            ->where('periode', $validated['periode'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Une évaluation existe déjà pour cet employé sur cette période',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $evaluation = Evaluation::create([
                'employe_id' => $validated['employe_id'],
                'evaluateur_id' => Auth::id(),
                'date_evaluation' => now(),
                'periode' => $validated['periode'],
                'points_forts' => $validated['points_forts'] ?? null,
                'axes_amelioration' => $validated['axes_amelioration'] ?? null,
                'objectifs' => $validated['objectifs'] ?? null,
                'commentaire_general' => $validated['commentaire_general'] ?? null,
                'statut' => $validated['statut'] ?? 'brouillon',
            ]);

            // Ajouter les détails
            foreach ($validated['notes'] as $note) {
                EvaluationDetail::create([
                    'evaluation_id' => $evaluation->id,
                    'critere_id' => $note['critere_id'],
                    'note' => $note['note'],
                    'commentaire' => $note['commentaire'] ?? null,
                ]);
            }

            // Calculer et mettre à jour le score global
            $evaluation->updateScoreGlobal();

            DB::commit();

            return response()->json([
                'message' => 'Évaluation créée avec succès',
                'data' => $evaluation->fresh()->load('details.critere', 'employe'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la création: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Met à jour une évaluation
     */
    public function update(Request $request, $id)
    {
        $evaluation = Evaluation::findOrFail($id);

        if ($evaluation->statut === 'archive') {
            return response()->json(['message' => 'Impossible de modifier une évaluation archivée'], 403);
        }

        $validated = $request->validate([
            'notes' => 'sometimes|array',
            'notes.*.critere_id' => 'required_with:notes|exists:criteres_evaluation,id',
            'notes.*.note' => 'required_with:notes|numeric|min:0|max:100',
            'notes.*.commentaire' => 'nullable|string',
            'points_forts' => 'nullable|string',
            'axes_amelioration' => 'nullable|string',
            'objectifs' => 'nullable|string',
            'commentaire_general' => 'nullable|string',
            'statut' => 'sometimes|in:brouillon,valide,archive',
        ]);

        DB::beginTransaction();
        try {
            $evaluation->update([
                'points_forts' => $validated['points_forts'] ?? $evaluation->points_forts,
                'axes_amelioration' => $validated['axes_amelioration'] ?? $evaluation->axes_amelioration,
                'objectifs' => $validated['objectifs'] ?? $evaluation->objectifs,
                'commentaire_general' => $validated['commentaire_general'] ?? $evaluation->commentaire_general,
                'statut' => $validated['statut'] ?? $evaluation->statut,
            ]);

            if (isset($validated['notes'])) {
                // Supprimer les anciens détails et recréer
                $evaluation->details()->delete();

                foreach ($validated['notes'] as $note) {
                    EvaluationDetail::create([
                        'evaluation_id' => $evaluation->id,
                        'critere_id' => $note['critere_id'],
                        'note' => $note['note'],
                        'commentaire' => $note['commentaire'] ?? null,
                    ]);
                }

                $evaluation->updateScoreGlobal();
            }

            DB::commit();

            return response()->json([
                'message' => 'Évaluation mise à jour',
                'data' => $evaluation->fresh()->load('details.critere', 'employe'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Supprime une évaluation
     */
    public function destroy($id)
    {
        $evaluation = Evaluation::findOrFail($id);

        if ($evaluation->statut === 'valide') {
            return response()->json(['message' => 'Impossible de supprimer une évaluation validée'], 403);
        }

        $evaluation->delete();

        return response()->json(['message' => 'Évaluation supprimée']);
    }

    /**
     * Historique des évaluations d'un employé
     */
    public function historiqueEmploye($employeId)
    {
        $employe = Employe::findOrFail($employeId);

        $evaluations = Evaluation::with('details.critere')
            ->where('employe_id', $employeId)
            ->where('statut', 'valide')
            ->orderByDesc('periode')
            ->get();

        // Calculer la progression
        $progression = [];
        if ($evaluations->count() >= 2) {
            $derniere = $evaluations->first();
            $precedente = $evaluations->skip(1)->first();
            $evolution = $derniere->score_global - $precedente->score_global;
            $progression = [
                'derniere' => $derniere->score_global,
                'precedente' => $precedente->score_global,
                'evolution' => round($evolution, 2),
                'tendance' => $evolution > 0 ? 'hausse' : ($evolution < 0 ? 'baisse' : 'stable'),
            ];
        }

        return response()->json([
            'employe' => [
                'id' => $employe->id,
                'nom' => $employe->nom,
                'prenom' => $employe->prenom,
            ],
            'evaluations' => $evaluations,
            'progression' => $progression,
            'moyenne_globale' => round($evaluations->avg('score_global'), 2),
        ]);
    }

    /**
     * Statistiques des évaluations par période
     */
    public function statistiques(Request $request)
    {
        $periode = $request->get('periode', now()->format('Y-m'));
        $annee = $request->get('annee', now()->format('Y'));

        // Stats de la période sélectionnée
        $statsPeriode = Evaluation::where('statut', 'valide')
            ->where('periode', $periode)
            ->selectRaw('
                COUNT(*) as total,
                AVG(score_global) as moyenne,
                MIN(score_global) as minimum,
                MAX(score_global) as maximum
            ')
            ->first();

        // Distribution des scores
        $distribution = [
            ['label' => 'Excellent (90-100%)', 'min' => 90, 'max' => 100, 'count' => 0],
            ['label' => 'Très bien (75-89%)', 'min' => 75, 'max' => 89, 'count' => 0],
            ['label' => 'Bien (60-74%)', 'min' => 60, 'max' => 74, 'count' => 0],
            ['label' => 'Satisfaisant (50-59%)', 'min' => 50, 'max' => 59, 'count' => 0],
            ['label' => 'À améliorer (40-49%)', 'min' => 40, 'max' => 49, 'count' => 0],
            ['label' => 'Insuffisant (< 40%)', 'min' => 0, 'max' => 39, 'count' => 0],
        ];

        $evaluations = Evaluation::where('statut', 'valide')
            ->where('periode', $periode)
            ->get();

        foreach ($evaluations as $eval) {
            foreach ($distribution as &$dist) {
                if ($eval->score_global >= $dist['min'] && $eval->score_global <= $dist['max']) {
                    $dist['count']++;
                    break;
                }
            }
        }

        // Évolution mensuelle sur l'année
        $evolutionMensuelle = Evaluation::where('statut', 'valide')
            ->where('periode', 'like', $annee . '%')
            ->selectRaw('periode, AVG(score_global) as moyenne, COUNT(*) as total')
            ->groupBy('periode')
            ->orderBy('periode')
            ->get();

        // Top critères (les mieux notés)
        $topCriteres = EvaluationDetail::join('evaluations', 'evaluation_details.evaluation_id', '=', 'evaluations.id')
            ->join('criteres_evaluation', 'evaluation_details.critere_id', '=', 'criteres_evaluation.id')
            ->where('evaluations.statut', 'valide')
            ->where('evaluations.periode', $periode)
            ->selectRaw('criteres_evaluation.libelle, AVG(evaluation_details.note) as moyenne')
            ->groupBy('criteres_evaluation.id', 'criteres_evaluation.libelle')
            ->orderByDesc('moyenne')
            ->get();

        return response()->json([
            'periode' => $periode,
            'stats' => [
                'total' => $statsPeriode->total ?? 0,
                'moyenne' => round($statsPeriode->moyenne ?? 0, 2),
                'minimum' => round($statsPeriode->minimum ?? 0, 2),
                'maximum' => round($statsPeriode->maximum ?? 0, 2),
            ],
            'distribution' => array_map(fn($d) => ['label' => $d['label'], 'value' => $d['count']], $distribution),
            'evolution_mensuelle' => $evolutionMensuelle,
            'top_criteres' => $topCriteres,
        ]);
    }

    /**
     * Génère le rapport PDF d'une évaluation
     */
    public function genererPdf($id)
    {
        $evaluation = Evaluation::with([
            'employe.poste',
            'employe.departement',
            'evaluateur',
            'details.critere'
        ])->findOrFail($id);

        // Utiliser le service PDF existant ou générer un PDF simple
        $pdf = app('dompdf.wrapper');
        
        $html = view('pdf.evaluation', compact('evaluation'))->render();
        $pdf->loadHTML($html);

        $filename = 'evaluation_' . $evaluation->employe->matricule . '_' . $evaluation->periode . '.pdf';

        return $pdf->download($filename);
    }
}
