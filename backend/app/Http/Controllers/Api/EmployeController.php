<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeRequest;
use App\Models\Employe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');

            $employes = Employe::with(['poste', 'departement'])
                ->search($search)
                ->orderBy('nom')
                ->paginate(10);

            return response()->json($employes);
        } catch (\Throwable $e) {
            Log::error('Erreur liste employes', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(EmployeRequest $request)
    {
        try {
            $employe = Employe::create($request->validated());
            return response()->json($employe, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation employe', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return Employe::with(['poste', 'departement', 'contrats', 'documents'])->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show employe', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(EmployeRequest $request, $id)
    {
        try {
            $employe = Employe::findOrFail($id);
            $payload = $request->validated();

            $ancienPoste = $employe->poste_id;
            $ancienDepartement = $employe->departement_id;

            $employe->update($payload);

            if (array_key_exists('poste_id', $payload) && $payload['poste_id'] !== $ancienPoste) {
                $employe->ajouterChangementPoste(
                    $payload['poste_id'],
                    $payload['departement_id'] ?? $ancienDepartement,
                    'Changement de poste automatique'
                );
            }

            return response()->json($employe);
        } catch (\Throwable $e) {
            Log::error('Erreur update employe', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Employe::findOrFail($id)->delete();
            return response()->json(['message' => 'Employé supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression employe', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
