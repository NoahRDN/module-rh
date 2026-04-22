<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HistoriquePosteRequest;
use App\Models\HistoriquePoste;
use App\Models\Employe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class HistoriquePosteController extends Controller
{
    public function index(Request $request)
    {
        try {
            $emp = $request->query('employe_id');

            $query = HistoriquePoste::with(['employe', 'poste', 'departement'])
                ->orderBy('date_changement', 'desc');

            if ($emp) {
                $query->where('employe_id', $emp);
            }

            return response()->json($query->paginate(10));
        } catch (\Throwable $e) {
            Log::error('Erreur liste historique postes', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(HistoriquePosteRequest $request)
    {
        $validated = $request->validated();

        try {
            $employe = Employe::find($validated['employe_id']);
            if (!$employe) {
                return response()->json(['message' => 'Employé introuvable'], 404);
            }

            $current = HistoriquePoste::where('employe_id', $validated['employe_id'])
                ->whereNotNull('poste_id')
                ->whereDate('date_changement', '<=', Carbon::today()->toDateString())
                ->orderByDesc('date_changement')
                ->first();

            $minAllowed = null;
            if ($current) {
                $minAllowed = Carbon::parse($current->date_changement)->startOfDay();
            } elseif ($employe->date_embauche) {
                $minAllowed = Carbon::parse($employe->date_embauche)->startOfDay();
            }

            if ($minAllowed) {
                $requested = Carbon::parse($validated['date_changement'])->startOfDay();
                if ($requested->lt($minAllowed)) {
                    return response()->json([
                        'message' => sprintf(
                            'Date invalide : la mobilité ne peut pas être antérieure au poste actuel (depuis le %s).',
                            $minAllowed->toDateString()
                        ),
                        'min_date' => $minAllowed->toDateString(),
                    ], 422);
                }
            }
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Données invalides', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur validation historique poste', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }

        try {
            $record = HistoriquePoste::create($validated);
            $this->rafraichirPosteCourant($record->employe_id);
            return response()->json($record, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation historique poste', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return HistoriquePoste::with(['employe', 'poste', 'departement'])
                ->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show historique poste', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $record = HistoriquePoste::findOrFail($id);
            $employeId = $record->employe_id;
            $record->delete();
            $this->rafraichirPosteCourant($employeId);
            return response()->json(['message' => 'Entrée supprimée']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression historique poste', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Met à jour le poste/département courant de l'employé en fonction du dernier historique effectif.
     */
    private function rafraichirPosteCourant(int $employeId): void
    {
        try {
            $latest = HistoriquePoste::where('employe_id', $employeId)
                ->whereDate('date_changement', '<=', Carbon::today()->toDateString())
                ->orderByDesc('date_changement')
                ->first();

            $employe = Employe::find($employeId);
            if (!$employe) {
                return;
            }

            if ($latest && $latest->poste_id) {
                $payload = ['poste_id' => $latest->poste_id];
                if ($latest->departement_id) {
                    $payload['departement_id'] = $latest->departement_id;
                }
                $employe->update($payload);
            }
        } catch (\Throwable $e) {
            Log::warning('Rafraîchissement poste courant échoué', ['employe_id' => $employeId, 'error' => $e->getMessage()]);
        }
    }
}
