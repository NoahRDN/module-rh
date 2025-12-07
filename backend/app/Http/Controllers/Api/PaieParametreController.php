<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaieParametreRequest;
use App\Models\PaieParametre;
use Illuminate\Support\Facades\Log;

class PaieParametreController extends Controller
{
    public function index()
    {
        try {
            $param = PaieParametre::first();
            return response()->json($param);
        } catch (\Throwable $e) {
            Log::error('Erreur fetch paie_parametres', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(PaieParametreRequest $request, $id)
    {
        try {
            $param = PaieParametre::findOrFail($id);
            $param->update($request->validated());
            return response()->json($param);
        } catch (\Throwable $e) {
            Log::error('Erreur update paie_parametres', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
