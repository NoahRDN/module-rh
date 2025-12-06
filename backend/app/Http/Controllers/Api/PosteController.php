<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosteRequest;
use App\Models\Poste;
use Illuminate\Http\Request;

class PosteController extends Controller
{
    public function index(Request $request)
    {
        $dep = $request->query('departement_id');

        $query = Poste::with('departement')->orderBy('nom');

        if ($dep) {
            $query->where('departement_id', $dep);
        }

        return response()->json($query->paginate(10));
    }

    public function store(PosteRequest $request)
    {
        $poste = Poste::create($request->validated());
        return response()->json($poste, 201);
    }

    public function show($id)
    {
        return Poste::with(['departement', 'employes'])->findOrFail($id);
    }

    public function update(PosteRequest $request, $id)
    {
        $poste = Poste::findOrFail($id);
        $poste->update($request->validated());

        return response()->json($poste);
    }

    public function destroy($id)
    {
        Poste::findOrFail($id)->delete();

        return response()->json(['message' => 'Poste supprimé']);
    }
}
