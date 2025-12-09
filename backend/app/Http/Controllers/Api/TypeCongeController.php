<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TypeCongeRequest;
use App\Models\TypeConge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TypeCongeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $query = TypeConge::query()->orderBy('libelle');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('libelle', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }
            return response()->json($query->paginate(10));
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
