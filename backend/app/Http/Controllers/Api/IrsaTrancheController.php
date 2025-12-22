<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IrsaTranche;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IrsaTrancheController extends Controller
{
    public function index()
    {
        return IrsaTranche::orderBy('min_base')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'min_base' => 'required|numeric|min:0',
            'max_base' => 'nullable|numeric|min:0',
            'taux' => 'required|numeric|min:0',
        ]);

        $row = IrsaTranche::create($data);
        return response()->json($row, 201);
    }

    public function update(Request $request, $id)
    {
        $row = IrsaTranche::findOrFail($id);
        $data = $request->validate([
            'min_base' => 'required|numeric|min:0',
            'max_base' => 'nullable|numeric|min:0',
            'taux' => 'required|numeric|min:0',
        ]);
        $row->update($data);
        return response()->json($row);
    }

    public function destroy($id)
    {
        try {
            IrsaTranche::findOrFail($id)->delete();
            return response()->json(['message' => 'Supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression irsa tranche', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
