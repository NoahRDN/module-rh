<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HistoriquePosteRequest;
use App\Models\HistoriquePoste;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HistoriquePosteController extends Controller
{
    public function index(Request $request)
    {
        try {
            $emp = $request->query('employe_id');

            $query = HistoriquePoste::with(['employe', 'poste', 'departement'])
                ->orderBy('date_changement', 'desc');

            if ($emp) {
                $query->where('employe_id', $emp);
            }

            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste historique postes', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(HistoriquePosteRequest $request)
    {
        try {
            $record = HistoriquePoste::create($request->validated());
            return response()->json($record, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation historique poste', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return HistoriquePoste::with(['employe', 'poste', 'departement'])
                ->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show historique poste', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            HistoriquePoste::findOrFail($id)->delete();
            return response()->json(['message' => 'Entrée supprimée']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression historique poste', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
