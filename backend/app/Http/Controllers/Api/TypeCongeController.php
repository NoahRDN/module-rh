<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TypeCongeRequest;
use App\Models\TypeConge;
use App\Models\ViewTypeCongeFull;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TypeCongeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $query = ViewTypeCongeFull::query()->orderBy('libelle');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('libelle', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }
            if ($request->filled('payant')) {
                $query->where('paye', $request->query('payant') === 'oui');
            }
            if ($request->filled('jours_min')) {
                $query->where('jours_forfait', '>=', (float) $request->query('jours_min'));
            }
            if ($request->filled('jours_max')) {
                $query->where('jours_forfait', '<=', (float) $request->query('jours_max'));
            }

            $page = $query->paginate(10);

            // Adapter le format pour rester compatible avec le front (frequence / cumulable_frequence imbriqués)
            $page->getCollection()->transform(function ($row) {
                $row->frequence = $row->frequence_id ? [
                    'id' => $row->frequence_id,
                    'code' => $row->frequence_code,
                    'libelle' => $row->frequence_libelle,
                ] : null;
                $row->limite_frequence = $row->limite_frequence_id ? [
                    'id' => $row->limite_frequence_id,
                    'code' => $row->limite_frequence_code,
                    'libelle' => $row->limite_frequence_libelle,
                ] : null;
                $row->cumulable_frequence = $row->cumulable_frequence_id ? [
                    'id' => $row->cumulable_frequence_id,
                    'code' => $row->cumulable_frequence_code,
                    'libelle' => $row->cumulable_frequence_libelle,
                ] : null;
                return $row;
            });

            return response()->json($page);
        } catch (\Throwable $e) {
            Log::error('Erreur liste types conges', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(TypeCongeRequest $request)
    {
        try {
            $type = TypeConge::create($request->validated());
            return response()->json($type, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation type conge', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        return TypeConge::findOrFail($id);
    }

    public function update(TypeCongeRequest $request, $id)
    {
        try {
            $type = TypeConge::findOrFail($id);
            $type->update($request->validated());
            return response()->json($type);
        } catch (\Throwable $e) {
            Log::error('Erreur update type conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            TypeConge::findOrFail($id)->delete();
            return response()->json(['message' => 'Type supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression type conge', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
