<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartementRequest;
use App\Models\Departement;

class DepartementController extends Controller
{
    public function index()
    {
        $list = Departement::orderBy('nom')->paginate(10);
        return response()->json($list);
    }

    public function store(DepartementRequest $request)
    {
        $dep = Departement::create($request->validated());
        return response()->json($dep, 201);
    }

    public function show($id)
    {
        return Departement::with('postes')->findOrFail($id);
    }

    public function update(DepartementRequest $request, $id)
    {
        $dep = Departement::findOrFail($id);
        $dep->update($request->validated());

        return response()->json($dep);
    }

    public function destroy($id)
    {
        Departement::findOrFail($id)->delete();

        return response()->json(['message' => 'Département supprimé']);
    }
}
