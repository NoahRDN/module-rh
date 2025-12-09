<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AbsenceTypeRequest;
use App\Models\AbsenceType;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AbsenceTypeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $query = AbsenceType::query()->orderBy('nom');
            if ($search) {
                $query->where('nom', 'like', "%{$search}%");
            }
            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste types absence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(AbsenceTypeRequest $request)
    {
        try {
            $type = AbsenceType::create($request->validated());
            return response()->json($type, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation type absence', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return AbsenceType::findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show type absence', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(AbsenceTypeRequest $request, $id)
    {
        try {
            $type = AbsenceType::findOrFail($id);
            $type->update($request->validated());
            return response()->json($type);
        } catch (\Throwable $e) {
            Log::error('Erreur update type absence', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            AbsenceType::findOrFail($id)->delete();
            return response()->json(['message' => 'Type d\'absence supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression type absence', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
