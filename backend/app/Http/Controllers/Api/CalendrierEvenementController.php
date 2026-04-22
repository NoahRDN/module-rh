<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendrierEvenement;
use App\Models\JourFerie;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalendrierEvenementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = CalendrierEvenement::with('employe')->orderBy('date_debut', 'asc');
            $from = $request->query('from');
            $to = $request->query('to');

            if ($request->filled('type')) {
                $query->where('type', $request->query('type'));
            }

            if ($from && $to) {
                $query
                    ->whereDate('date_debut', '<=', $to)
                    ->whereDate('date_fin', '>=', $from);
            } elseif ($from) {
                $query->whereDate('date_fin', '>=', $from);
            } elseif ($to) {
                $query->whereDate('date_debut', '<=', $to);
            }

            // Inclure également les jours fériés si demandés
            $includeFeries = !$request->filled('type') || $request->query('type') === 'ferie';

            // Convertir en collection générique pour pouvoir fusionner sans erreur
            // les modèles Eloquent et les tableaux des jours fériés calculés.
            $events = collect($query->get()->all());

            if ($includeFeries) {
                $feries = $this->holidayEventsForRange($from, $to);
                $events = $events->merge($feries);
            }

            $events = $events
                ->sortBy(fn ($event) => sprintf(
                    '%s-%s',
                    data_get($event, 'date_debut', ''),
                    data_get($event, 'type', '')
                ))
                ->values();

            $response = response()->json([
                'data' => $events,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $events->count(),
                    'total' => $events->count(),
                ],
            ]);
            Log::info('Liste calendrier evenements', [
                'from' => $from,
                'to' => $to,
                'type' => $request->query('type'),
                'total' => $events->count(),
            ]);
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

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'employe_id'  => 'nullable|exists:employes,id',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after_or_equal:date_debut',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $event = CalendrierEvenement::findOrFail($id);
            if ($event->type !== 'rh') {
                return response()->json([
                    'message' => 'Seuls les événements RH créés dans ce calendrier peuvent être modifiés ici.',
                ], 422);
            }

            $event->update($data);
            $event->load('employe');

            return response()->json($event);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Événement non trouvé'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour event calendrier', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $event = CalendrierEvenement::findOrFail($id);
            if ($event->type !== 'rh') {
                return response()->json([
                    'message' => 'Seuls les événements RH créés dans ce calendrier peuvent être supprimés ici.',
                ], 422);
            }

            $event->delete();

            return response()->json(['message' => 'Événement supprimé']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Événement non trouvé'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression event calendrier', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    private function holidayEventsForRange(?string $from, ?string $to)
    {
        $fromDate = $from
            ? Carbon::parse($from)->startOfDay()
            : now()->copy()->startOfYear();
        $toDate = $to
            ? Carbon::parse($to)->endOfDay()
            : now()->copy()->endOfYear();

        $singleHolidayEvents = JourFerie::query()
            ->where('recurrent', false)
            ->whereNotNull('date')
            ->whereDate('date', '>=', $fromDate->toDateString())
            ->whereDate('date', '<=', $toDate->toDateString())
            ->get()
            ->map(fn (JourFerie $holiday) => $this->formatHolidayEvent($holiday, $holiday->date))
            ->filter()
            ->values();

        $recurringHolidayEvents = JourFerie::query()
            ->where('recurrent', true)
            ->get()
            ->flatMap(function (JourFerie $holiday) use ($fromDate, $toDate) {
                $events = [];

                for ($year = $fromDate->year; $year <= $toDate->year; $year += 1) {
                    $occurrence = $this->holidayOccurrenceForYear($holiday, $year);
                    if (!$occurrence) {
                        continue;
                    }

                    if ($occurrence->lt($fromDate) || $occurrence->gt($toDate)) {
                        continue;
                    }

                    $payload = $this->formatHolidayEvent($holiday, $occurrence, $year);
                    if ($payload) {
                        $events[] = $payload;
                    }
                }

                return $events;
            });

        // Important: convert to base collections so merge() doesn't assume Eloquent models.
        return collect($singleHolidayEvents->all())->merge(collect($recurringHolidayEvents->all()));
    }

    private function holidayOccurrenceForYear(JourFerie $holiday, int $year): ?Carbon
    {
        try {
            return Carbon::createFromDate(
                $year,
                (int) $holiday->date->format('m'),
                (int) $holiday->date->format('d')
            )->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatHolidayEvent(JourFerie $holiday, $occurrence, ?int $year = null): ?array
    {
        if (!$occurrence) {
            return null;
        }

        try {
            $date = $occurrence instanceof Carbon
                ? $occurrence->copy()
                : Carbon::parse($occurrence)->startOfDay();
        } catch (\Throwable $e) {
            Log::warning('Jour férié ignoré: date invalide', [
                'holiday_id' => $holiday->id,
                'recurrent' => $holiday->recurrent,
                'raw_date' => $occurrence,
            ]);
            return null;
        }

        return [
            'id' => 'ferie-' . $holiday->id . ($year ? '-' . $year : ''),
            'type' => 'ferie',
            'employe_id' => null,
            'date_debut' => $date->toDateString(),
            'date_fin' => $date->toDateString(),
            'description' => $holiday->nom,
            'meta' => [
                'recurrent' => $holiday->recurrent,
                'occurrence_year' => $year,
            ],
            'employe' => null,
        ];
    }
}
