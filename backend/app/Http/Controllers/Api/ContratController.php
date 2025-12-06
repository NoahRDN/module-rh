<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContratRequest;
use App\Models\Contrat;
use Illuminate\Http\Request;

class ContratController extends Controller
{
    public function index(Request $request)
    {
        $employe = $request->query('employe_id');

        $query = Contrat::with('employe')->orderBy('date_debut', 'desc');

        if ($employe) {
            $query->where('employe_id', $employe);
        }

        return response()->json($query->paginate(10));
    }

    public function store(ContratRequest $request)
    {
        $contrat = Contrat::create($request->validated());
        return response()->json($contrat, 201);
    }

    public function show($id)
    {
        return Contrat::with('employe')->findOrFail($id);
    }

    public function update(ContratRequest $request, $id)
    {
        $contrat = Contrat::findOrFail($id);
        $contrat->update($request->validated());

        return response()->json($contrat);
    }

    public function destroy($id)
    {
        Contrat::findOrFail($id)->delete();
        return response()->json(['message' => 'Contrat supprimé']);
    }
}
