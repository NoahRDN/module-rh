<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RemunerationItemRequest;
use App\Models\RemunerationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RemunerationItemController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = RemunerationItem::with([
                'poste:id,nom',
                'employe:id,matricule,nom,prenom',
                'contrat:id,numero',
            ])->orderByDesc('id');

            if ($nature = $request->query('nature')) {
                $query->where('nature', $nature);
            }

            if ($scope = $request->query('scope_type')) {
                $query->where('scope_type', $scope);
            }

            if (($actif = $request->query('actif')) !== null && $actif !== '') {
                $query->where('actif', filter_var($actif, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool) $actif);
            }

            return response()->json($query->get());
        } catch (\Throwable $e) {
            Log::error('Erreur liste remuneration items', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return response()->json(
                RemunerationItem::with(['poste:id,nom', 'employe:id,matricule,nom,prenom', 'contrat:id,numero'])->findOrFail($id)
            );
        } catch (\Throwable $e) {
            Log::error('Erreur détail remuneration item', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Élément introuvable'], 404);
        }
    }

    public function store(RemunerationItemRequest $request)
    {
        try {
            $item = RemunerationItem::create($this->normalizePayload($request->validated()));
            return response()->json($item->load(['poste:id,nom', 'employe:id,matricule,nom,prenom', 'contrat:id,numero']), 201);
        } catch (\Throwable $e) {
            Log::error('Erreur création remuneration item', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(RemunerationItemRequest $request, $id)
    {
        try {
            $item = RemunerationItem::findOrFail($id);
            $item->update($this->normalizePayload($request->validated()));
            return response()->json($item->fresh(['poste:id,nom', 'employe:id,matricule,nom,prenom', 'contrat:id,numero']));
        } catch (\Throwable $e) {
            Log::error('Erreur update remuneration item', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $item = RemunerationItem::findOrFail($id);
            $item->delete();
            return response()->json(['message' => 'Élément supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression remuneration item', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    protected function normalizePayload(array $payload): array
    {
        if (($payload['scope_type'] ?? null) !== 'poste') {
            $payload['poste_id'] = null;
        }

        if (($payload['scope_type'] ?? null) !== 'employe') {
            $payload['employe_id'] = null;
        }

        if (($payload['scope_type'] ?? null) !== 'contrat') {
            $payload['contrat_id'] = null;
        }

        if (($payload['recurrence_type'] ?? null) !== 'ponctuel') {
            $payload['mois_application'] = null;
        }

        if (empty($payload['condition_type'])) {
            $payload['condition_type'] = null;
            $payload['condition_operator'] = null;
            $payload['condition_value'] = null;
        }

        $payload['is_taxable'] = array_key_exists('is_taxable', $payload) ? (bool) $payload['is_taxable'] : true;
        $payload['actif'] = array_key_exists('actif', $payload) ? (bool) $payload['actif'] : true;

        return $payload;
    }
}
