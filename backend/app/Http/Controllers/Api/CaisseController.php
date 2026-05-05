<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateCaisseSyntheseDayJob;
use App\Models\Caisse;
use App\Models\CaisseMouvement;
use App\Models\CaisseSyntheseJournaliere;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaisseController extends Controller
{
    private const READ_MODEL_STALE_MINUTES = 30;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'caisse_id' => 'nullable|exists:caisses,id',
            'type' => 'nullable|in:entree,sortie',
            'categorie' => 'nullable|string|max:64',
            'statut' => 'nullable|in:tous,en_attente_validation,valide,rejete',
            'active' => 'nullable|in:1,0,true,false',
        ]);

        $categories = $this->categories();

        $caisses = Caisse::query()
            ->when($request->has('active'), fn ($query) => $query->where('active', filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('nom')
            ->get();

        $mouvements = CaisseMouvement::with([
                'caisse:id,nom,solde',
                'paie.employe:id,matricule,nom,prenom',
            ])
            ->when(!empty($validated['caisse_id']), fn ($query) => $query->where('caisse_id', $validated['caisse_id']))
            ->when(!empty($validated['type']), fn ($query) => $query->where('type', $validated['type']))
            ->when(!empty($validated['categorie']), fn ($query) => $query->where('categorie', $validated['categorie']))
            ->when(!empty($validated['statut']) && $validated['statut'] !== 'tous', fn ($query) => $query->where('statut', $validated['statut']))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'caisses' => $caisses,
            'mouvements' => $mouvements,
            'categories' => $categories,
            'synthese' => $this->caisseSynthesePayload(now()->toDateString()),
            'synthese_status' => $this->caisseSyntheseState(now()->toDateString()),
        ]);
    }

    public function types()
    {
        return response()->json([
            'caisses' => Caisse::orderBy('nom')->get(),
            'categories' => $this->categories(),
        ]);
    }

    public function storeType(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:120|unique:caisses,nom',
            'description' => 'nullable|string|max:500',
            'solde' => 'nullable|numeric|min:0',
            'active' => 'nullable|boolean',
        ]);

        try {
            $caisse = Caisse::create([
                'nom' => trim($data['nom']),
                'description' => $data['description'] ?? null,
                'solde' => $data['solde'] ?? 0,
                'active' => $data['active'] ?? true,
            ]);

            return response()->json([
                'message' => 'Type de caisse créé',
                'caisse' => $caisse,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erreur création type caisse', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function toggleActive($id)
    {
        try {
            $caisse = Caisse::findOrFail($id);
            $caisse->update(['active' => !$caisse->active]);

            return response()->json([
                'message' => $caisse->active ? 'Type de caisse activé' : 'Type de caisse désactivé',
                'caisse' => $caisse,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur toggle type caisse', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function storeMouvement(Request $request)
    {
        $categories = $this->categories();
        $data = $request->validate([
            'caisse_id' => 'required|exists:caisses,id',
            'type' => 'required|in:entree,sortie',
            'categorie' => 'required|string|max:64',
            'montant' => 'required|numeric|min:0.01',
            'source' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            if (!$this->isValidCategory($data['type'], $data['categorie'], $categories)) {
                return response()->json(['message' => 'Catégorie de mouvement invalide pour ce type'], 422);
            }

            if (!Caisse::where('id', $data['caisse_id'])->where('active', true)->exists()) {
                return response()->json(['message' => 'Cette caisse est désactivée'], 422);
            }

            $mouvement = CaisseMouvement::create([
                ...$data,
                'statut' => 'en_attente_validation',
                'demande_validation_le' => now(),
            ]);
            $this->refreshCaisseSyntheseForMouvement($mouvement);

            return response()->json([
                'message' => 'Mouvement de caisse soumis à validation',
                'mouvement' => $mouvement->load('caisse'),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erreur création mouvement caisse', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function enAttente()
    {
        $categories = $this->categories();
        $mouvements = CaisseMouvement::with([
                'caisse:id,nom,solde',
                'paie.employe:id,matricule,nom,prenom',
            ])
            ->where('statut', 'en_attente_validation')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'mouvements' => $mouvements,
            'categories' => $categories,
        ]);
    }

    private function categories(): array
    {
        return config('caisse_mouvements.categories', ['entree' => [], 'sortie' => []]);
    }

    private function isValidCategory(string $type, string $categorie, array $categories): bool
    {
        return collect($categories[$type] ?? [])->pluck('code')->contains($categorie);
    }

    public function valider($id)
    {
        try {
            $mouvement = DB::transaction(function () use ($id) {
                $mouvement = CaisseMouvement::with('paie')->lockForUpdate()->findOrFail($id);

                if ($mouvement->statut !== 'en_attente_validation') {
                    abort(422, 'Ce mouvement a déjà été traité');
                }

                $caisse = Caisse::lockForUpdate()->findOrFail($mouvement->caisse_id);
                $montant = (float) $mouvement->montant;

                if ($mouvement->type === 'sortie' && (float) $caisse->solde < $montant) {
                    abort(422, 'Solde de caisse insuffisant pour valider cette sortie');
                }

                $caisse->solde = $mouvement->type === 'entree'
                    ? (float) $caisse->solde + $montant
                    : (float) $caisse->solde - $montant;
                $caisse->save();

                $mouvement->update([
                    'statut' => 'valide',
                    'valide_le' => now(),
                ]);

                if ($mouvement->paie) {
                    $mouvement->paie->update([
                        'statut' => 'paye',
                        'paye_le' => now()->toDateString(),
                        'valide_le' => $mouvement->paie->valide_le ?: now(),
                    ]);
                }

                return $mouvement->fresh(['caisse:id,nom,solde', 'paie.employe:id,matricule,nom,prenom']);
            });
            $this->refreshCaisseSyntheseForMouvement($mouvement);
            if ($mouvement->paie) {
                app(PaieController::class)->refreshPaieSyntheseMonth($mouvement->paie->mois, [$mouvement->paie->employe_id]);
            }

            return response()->json([
                'message' => 'Mouvement de caisse validé',
                'mouvement' => $mouvement,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Erreur validation mouvement caisse', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function rejeter($id)
    {
        try {
            $mouvement = DB::transaction(function () use ($id) {
                $mouvement = CaisseMouvement::with('paie')->lockForUpdate()->findOrFail($id);

                if ($mouvement->statut !== 'en_attente_validation') {
                    abort(422, 'Ce mouvement a déjà été traité');
                }

                $mouvement->update([
                    'statut' => 'rejete',
                    'rejete_le' => now(),
                ]);

                if ($mouvement->paie && $mouvement->paie->statut === 'paiement_en_validation') {
                    $mouvement->paie->update(['statut' => 'non_paye']);
                }

                return $mouvement->fresh(['caisse:id,nom,solde', 'paie.employe:id,matricule,nom,prenom']);
            });
            $this->refreshCaisseSyntheseForMouvement($mouvement);
            if ($mouvement->paie) {
                app(PaieController::class)->refreshPaieSyntheseMonth($mouvement->paie->mois, [$mouvement->paie->employe_id]);
            }

            return response()->json([
                'message' => 'Mouvement de caisse rejeté',
                'mouvement' => $mouvement,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Erreur rejet mouvement caisse', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function refreshCaisseSyntheseDay(?string $date = null, ?array $caisseIds = null): int
    {
        $day = Carbon::parse($date ?: now())->startOfDay();
        $end = $day->copy()->endOfDay();
        $caisseIds = $caisseIds ? array_values(array_unique(array_map('intval', $caisseIds))) : null;
        $caisses = Caisse::query()
            ->select(['id', 'solde'])
            ->when($caisseIds, fn ($query) => $query->whereIn('id', $caisseIds))
            ->get();

        $mouvements = CaisseMouvement::query()
            ->select(['id', 'caisse_id', 'type', 'categorie', 'montant', 'statut', 'created_at'])
            ->whereBetween('created_at', [$day, $end])
            ->when($caisseIds, fn ($query) => $query->whereIn('caisse_id', $caisseIds))
            ->get()
            ->groupBy('caisse_id');

        $payloads = $caisses->map(function (Caisse $caisse) use ($day, $mouvements) {
            $items = $mouvements->get($caisse->id, collect());
            $valides = $items->where('statut', 'valide');
            $entrees = $valides->where('type', 'entree')->sum('montant');
            $sorties = $valides->where('type', 'sortie')->sum('montant');
            $parCategorie = $items
                ->groupBy(fn ($mouvement) => "{$mouvement->type}:{$mouvement->categorie}")
                ->map(function ($categoryItems, string $key) {
                    [$type, $categorie] = array_pad(explode(':', $key, 2), 2, null);

                    return [
                        'type' => $type,
                        'categorie' => $categorie,
                        'total' => round($categoryItems->sum('montant'), 2),
                        'count' => $categoryItems->count(),
                    ];
                })
                ->values()
                ->all();

            return [
                'caisse_id' => $caisse->id,
                'jour' => $day->toDateString(),
                'total_entrees' => round($entrees, 2),
                'total_sorties' => round($sorties, 2),
                'solde_net' => round($entrees - $sorties, 2),
                'solde_caisse' => (float) $caisse->solde,
                'mouvements_total' => $items->count(),
                'mouvements_valides' => $items->where('statut', 'valide')->count(),
                'mouvements_en_attente' => $items->where('statut', 'en_attente_validation')->count(),
                'mouvements_rejetes' => $items->where('statut', 'rejete')->count(),
                'par_categorie' => json_encode($parCategorie, JSON_UNESCAPED_UNICODE),
                'generated_at' => now(),
                'synthese_status' => 'available',
                'refreshed_by' => app()->runningInConsole() ? 'command' : 'http',
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        DB::transaction(function () use ($day, $caisseIds, $payloads) {
            $query = CaisseSyntheseJournaliere::query()->whereDate('jour', $day->toDateString());
            if ($caisseIds) {
                $query->whereIn('caisse_id', $caisseIds);
            }
            $query->delete();

            if ($payloads) {
                CaisseSyntheseJournaliere::query()->insert($payloads);
            }
        });

        return count($payloads);
    }

    private function caisseSynthesePayload(string $date): array
    {
        $day = Carbon::parse($date)->toDateString();
        $rows = CaisseSyntheseJournaliere::query()
            ->whereDate('jour', $day)
            ->get();
        $generatedAt = $rows->max('generated_at');

        return [
            'jour' => $day,
            'total_entrees' => round($rows->sum('total_entrees'), 2),
            'total_sorties' => round($rows->sum('total_sorties'), 2),
            'solde_net' => round($rows->sum('solde_net'), 2),
            'mouvements_total' => (int) $rows->sum('mouvements_total'),
            'mouvements_valides' => (int) $rows->sum('mouvements_valides'),
            'mouvements_en_attente' => (int) $rows->sum('mouvements_en_attente'),
            'mouvements_rejetes' => (int) $rows->sum('mouvements_rejetes'),
            'updated_at' => $generatedAt ? Carbon::parse($generatedAt)->toIso8601String() : null,
        ];
    }

    private function caisseSyntheseState(string $date): array
    {
        $day = Carbon::parse($date)->toDateString();
        $rows = CaisseSyntheseJournaliere::query()->whereDate('jour', $day)->get();
        $generatedAt = $rows->max('generated_at');
        $isGenerating = Cache::has($this->caisseSyntheseCacheKey($day));
        $isStale = $generatedAt ? Carbon::parse($generatedAt)->lt(now()->subMinutes($this->readModelStaleMinutes())) : false;

        if ($isGenerating) {
            return $this->caisseSyntheseMeta($day, 'generating', $generatedAt, $rows->count());
        }

        if ($rows->isEmpty() && Caisse::query()->exists()) {
            $this->queueCaisseSyntheseDay($day);
            return $this->caisseSyntheseMeta($day, 'generating', $generatedAt, 0);
        }

        if ($isStale) {
            $this->queueCaisseSyntheseDay($day);
            return $this->caisseSyntheseMeta($day, 'stale', $generatedAt, $rows->count());
        }

        return $this->caisseSyntheseMeta($day, 'available', $generatedAt, $rows->count());
    }

    private function caisseSyntheseMeta(string $day, string $status, ?string $generatedAt, int $count): array
    {
        return [
            'jour' => $day,
            'status' => $status,
            'message' => match ($status) {
                'generating' => 'La synthèse de caisse est en cours de génération.',
                'stale' => 'La synthèse de caisse est affichée, mais une mise à jour est en cours.',
                default => 'La synthèse de caisse est disponible.',
            },
            'generated_at' => $generatedAt ? Carbon::parse($generatedAt)->toIso8601String() : null,
            'updated_at' => CaisseSyntheseJournaliere::query()->whereDate('jour', $day)->max('updated_at'),
            'is_stale' => $status === 'stale',
            'rows' => $count,
        ];
    }

    private function queueCaisseSyntheseDay(string $day): void
    {
        if (!Cache::add($this->caisseSyntheseCacheKey($day), 'generating', now()->addMinutes(10))) {
            return;
        }

        GenerateCaisseSyntheseDayJob::dispatch($day)->afterResponse();
    }

    private function caisseSyntheseCacheKey(string $day): string
    {
        return "read-model:caisse-synthese:{$day}";
    }

    private function readModelStaleMinutes(): int
    {
        return max(1, (int) env('READ_MODEL_STALE_MINUTES', self::READ_MODEL_STALE_MINUTES));
    }

    private function refreshCaisseSyntheseForMouvement(CaisseMouvement $mouvement): void
    {
        try {
            $this->refreshCaisseSyntheseDay($mouvement->created_at?->toDateString() ?: now()->toDateString(), [$mouvement->caisse_id]);
        } catch (\Throwable $e) {
            Log::warning('Refresh synthese caisse échoué', [
                'mouvement_id' => $mouvement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
