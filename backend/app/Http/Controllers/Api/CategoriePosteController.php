<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoriePoste;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriePosteController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = CategoriePoste::orderBy('nom')->get();
        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:categorie_postes,nom',
            'code' => 'nullable|string|max:255|unique:categorie_postes,code',
            'description' => 'nullable|string',
        ]);

        $categorie = CategoriePoste::create($validated);

        return response()->json($categorie, 201);
    }

    public function show(string $id): JsonResponse
    {
        $categorie = CategoriePoste::findOrFail($id);
        return response()->json($categorie);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $categorie = CategoriePoste::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255|unique:categorie_postes,nom,' . $categorie->id,
            'code' => 'nullable|string|max:255|unique:categorie_postes,code,' . $categorie->id,
            'description' => 'nullable|string',
        ]);

        $categorie->update($validated);

        return response()->json($categorie);
    }

    public function destroy(string $id): JsonResponse
    {
        $categorie = CategoriePoste::findOrFail($id);
        $categorie->delete();

        return response()->json(['message' => 'Supprimé']);
    }
}
