<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CritereEvaluation;
use Illuminate\Http\Request;

class CritereEvaluationController extends Controller
{
    /**
     * Liste tous les critères d'évaluation
     */
    public function index()
    {
        $criteres = CritereEvaluation::orderBy('ordre')->get();
        return response()->json(['data' => $criteres]);
    }

    /**
     * Affiche un critère
     */
    public function show($id)
    {
        $critere = CritereEvaluation::findOrFail($id);
        return response()->json(['data' => $critere]);
    }

    /**
     * Crée un nouveau critère
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:criteres_evaluation,code',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'poids' => 'required|integer|min:1|max:100',
            'actif' => 'sometimes|boolean',
            'ordre' => 'sometimes|integer',
        ]);

        $critere = CritereEvaluation::create($validated);

        return response()->json([
            'message' => 'Critère créé avec succès',
            'data' => $critere,
        ], 201);
    }

    /**
     * Met à jour un critère
     */
    public function update(Request $request, $id)
    {
        $critere = CritereEvaluation::findOrFail($id);

        $validated = $request->validate([
            'libelle' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'poids' => 'sometimes|integer|min:1|max:100',
            'actif' => 'sometimes|boolean',
            'ordre' => 'sometimes|integer',
        ]);

        $critere->update($validated);

        return response()->json([
            'message' => 'Critère mis à jour',
            'data' => $critere->fresh(),
        ]);
    }

    /**
     * Supprime un critère
     */
    public function destroy($id)
    {
        $critere = CritereEvaluation::findOrFail($id);

        // Vérifier si le critère est utilisé dans des évaluations
        if ($critere->evaluationDetails()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce critère car il est utilisé dans des évaluations',
            ], 422);
        }

        $critere->delete();

        return response()->json(['message' => 'Critère supprimé']);
    }

    /**
     * Réordonne les critères
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ordres' => 'required|array',
            'ordres.*.id' => 'required|exists:criteres_evaluation,id',
            'ordres.*.ordre' => 'required|integer|min:0',
        ]);

        foreach ($validated['ordres'] as $item) {
            CritereEvaluation::where('id', $item['id'])->update(['ordre' => $item['ordre']]);
        }

        return response()->json(['message' => 'Ordre mis à jour']);
    }
}
