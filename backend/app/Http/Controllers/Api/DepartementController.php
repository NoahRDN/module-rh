<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartementRequest;
use App\Models\Departement;
use Illuminate\Support\Facades\Log;

class DepartementController extends Controller
{
    public function index()
    {
        try {
            $list = Departement::orderBy('nom')->paginate(10);
            return response()->json($list);
        } catch (\Throwable $e) {
            Log::error('Erreur liste departements', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(DepartementRequest $request)
    {
        try {
            $dep = Departement::create($request->validated());
            return response()->json($dep, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation departement', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return Departement::with('postes')->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show departement', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(DepartementRequest $request, $id)
    {
        try {
            $dep = Departement::findOrFail($id);
            $dep->update($request->validated());

            return response()->json($dep);
        } catch (\Throwable $e) {
            Log::error('Erreur update departement', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Departement::findOrFail($id)->delete();
            return response()->json(['message' => 'Département supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression departement', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
