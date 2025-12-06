<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HistoriquePosteRequest;
use App\Models\HistoriquePoste;
use Illuminate\Http\Request;

class HistoriquePosteController extends Controller
{
    public function index(Request $request)
    {
        $emp = $request->query('employe_id');

        $query = HistoriquePoste::with(['employe', 'poste', 'departement'])
            ->orderBy('date_changement', 'desc');

        if ($emp) {
            $query->where('employe_id', $emp);
        }

        return response()->json($query->paginate(10));
    }

    public function store(HistoriquePosteRequest $request)
    {
        $record = HistoriquePoste::create($request->validated());
        return response()->json($record, 201);
    }

    public function show($id)
    {
        return HistoriquePoste::with(['employe', 'poste', 'departement'])
            ->findOrFail($id);
    }

    public function destroy($id)
    {
        HistoriquePoste::findOrFail($id)->delete();
        return response()->json(['message' => 'Entrée supprimée']);
    }
}
