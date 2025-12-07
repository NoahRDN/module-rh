<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendrierEvenement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalendrierEvenementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = CalendrierEvenement::with('employe')->orderBy('date_debut', 'asc');
            if ($request->filled('type')) {
                $query->where('type', $request->query('type'));
            }
            if ($request->filled('from')) {
                $query->whereDate('date_debut', '>=', $request->query('from'));
            }
            if ($request->filled('to')) {
                $query->whereDate('date_fin', '<=', $request->query('to'));
            }
            return response()->json($query->paginate(20));
        } catch (\Throwable $e) {
            Log::error('Erreur liste calendrier', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'        => 'required|string|max:50',
            'employe_id'  => 'nullable|exists:employes,id',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after_or_equal:date_debut',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $event = CalendrierEvenement::create($data);
            return response()->json($event, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation event calendrier', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
