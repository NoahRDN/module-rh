<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeRequest;
use App\Models\Employe;
use Illuminate\Http\Request;

class EmployeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $employes = Employe::with(['poste', 'departement'])
            ->search($search)
            ->orderBy('nom')
            ->paginate(10);

        return response()->json($employes);
    }

    public function store(EmployeRequest $request)
    {
        $employe = Employe::create($request->validated());
        return response()->json($employe, 201);
    }

    public function show($id)
    {
        return Employe::with(['poste', 'departement', 'contrats', 'documents'])->findOrFail($id);
    }

    public function update(EmployeRequest $request, $id)
    {
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
    }

    public function destroy($id)
    {
        Employe::findOrFail($id)->delete();
        return response()->json(['message' => 'Employé supprimé']);
    }
}
