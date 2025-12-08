<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratHistorique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContratHistoriqueController extends Controller
{
    public function index(Request $request)
    {
        try {
            $employe = $request->query('employe_id');
            $query = ContratHistorique::with(['employe', 'contrat'])->orderByDesc('created_at');
            if ($employe) {
                $query->where('employe_id', $employe);
            }
            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste historique contrats', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
