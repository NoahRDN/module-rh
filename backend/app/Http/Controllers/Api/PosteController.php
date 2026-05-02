<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosteRequest;
use App\Models\Poste;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PosteController extends Controller
{
    public function index(Request $request)
    {
        try {
            $dep = $request->query('departement_id');
            $all = $request->boolean('all', false);

            $query = Poste::with('departement')->orderBy('nom');

            if ($dep) {
                $query->where('departement_id', $dep);
            }
            if ($request->filled('nom')) {
                $query->where('nom', 'ILIKE', '%' . $request->query('nom') . '%');
            }
            if ($request->filled('categorie')) {
                $query->where('categorie', 'ILIKE', '%' . $request->query('categorie') . '%');
            }

            return response()->json($all ? $query->get() : $query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste postes', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(PosteRequest $request)
    {
        try {
            $data = $request->validated();
            $data['categorie_level'] = $this->categorieLevel($data['categorie'] ?? null);

            $poste = Poste::create($data);
            return response()->json($poste, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation poste', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return Poste::with(['departement', 'employes'])->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show poste', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(PosteRequest $request, $id)
    {
        try {
            $poste = Poste::findOrFail($id);
            $data = $request->validated();
            $data['categorie_level'] = $this->categorieLevel($data['categorie'] ?? null);
            $poste->update($data);

            return response()->json($poste);
        } catch (\Throwable $e) {
            Log::error('Erreur update poste', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Poste::findOrFail($id)->delete();

            return response()->json(['message' => 'Poste supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression poste', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    protected function categorieLevel(?string $categorie): ?int
    {
        if (!$categorie) {
            return null;
        }
        $list = config('categories.list', []);
        foreach ($list as $item) {
            if (strcasecmp($item['code'], $categorie) === 0) {
                return (int) ($item['level'] ?? null);
            }
        }
        return null;
    }
}
