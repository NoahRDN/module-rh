<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendrierEvenement;
use App\Models\JourFerie;
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

            // Inclure également les jours fériés si demandés
            $includeFeries = !$request->filled('type') || $request->query('type') === 'ferie';

            $page = $query->paginate(20);
            $events = collect($page->items());

            if ($includeFeries) {
                $feries = JourFerie::all()->map(function ($f) {
                    return [
                        'id' => 'ferie-' . $f->id,
                        'type' => 'ferie',
                        'employe_id' => null,
                        'date_debut' => $f->date->toDateString(),
                        'date_fin' => $f->date->toDateString(),
                        'description' => $f->nom,
                        'meta' => ['recurrent' => $f->recurrent],
                        'employe' => null,
                    ];
                });
                $events = $events->merge($feries);
            }

            $response = response()->json([
                'data' => $events->values(),
                'meta' => [
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total() + ($includeFeries ? JourFerie::count() : 0),
                ],
            ]);
            Log::info('Liste calendrier evenements', [$response->content()]);
            return $response;
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
