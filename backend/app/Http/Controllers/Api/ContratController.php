<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContratRequest;
use App\Models\Contrat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContratController extends Controller
{
    public function index(Request $request)
    {
        try {
            $employe = $request->query('employe_id');

            $query = Contrat::with(['employe.departement', 'employe.poste'])->orderBy('date_debut', 'desc');

            if ($employe) {
                $query->where('employe_id', $employe);
            }

            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste contrats', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(ContratRequest $request)
    {
        try {
            $contrat = Contrat::create($request->validated());
            return response()->json($contrat, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation contrat', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return Contrat::with(['employe.departement', 'employe.poste'])->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show contrat', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(ContratRequest $request, $id)
    {
        try {
            $contrat = Contrat::findOrFail($id);
            $contrat->update($request->validated());

            return response()->json($contrat);
        } catch (\Throwable $e) {
            Log::error('Erreur update contrat', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Contrat::findOrFail($id)->delete();
            return response()->json(['message' => 'Contrat supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression contrat', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
