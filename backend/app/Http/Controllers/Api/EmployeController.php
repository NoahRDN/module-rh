<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeRequest;
use App\Models\Employe;
use App\Models\Poste;
use App\Models\User;
use App\Http\Controllers\Api\SoldeCongeController;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class EmployeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $activeOnly = $request->boolean('active_only', false);
            $all = $request->boolean('all', false);
            $perPage = min(100, max(1, (int) $request->query('per_page', 10)));
            $limit = min(500, max(1, (int) $request->query('limit', 500)));
            $sort = $request->query('sort', 'nom');
            $now = now()->toDateString();

            // Tri : par défaut alphabétique, ou par date de création si sort=recent
            $orderColumn = $sort === 'recent' ? 'created_at' : 'nom';
            $orderDirection = $sort === 'recent' ? 'desc' : 'asc';

            $query = Employe::query()
                ->select([
                    'id',
                    'matricule',
                    'nom',
                    'prenom',
                    'email',
                    'telephone',
                    'adresse',
                    'date_naissance',
                    'poste_id',
                    'departement_id',
                    'photo',
                    'date_embauche',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'poste:id,nom,departement_id,categorie,categorie_level',
                    'departement:id,nom',
                ])
                ->withExists(['contrats as actif' => function ($q) use ($now) {
                    $q->whereDate('date_debut', '<=', $now)
                        ->where(function ($w) use ($now) {
                            $w->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
                        });
                }])
                ->search($search)
                ->when($request->filled('matricule'), function ($q) use ($request) {
                    $q->where('matricule', 'ILIKE', '%' . $request->query('matricule') . '%');
                })
                ->when($request->filled('nom'), function ($q) use ($request) {
                    $term = $request->query('nom');
                    $q->where(function ($w) use ($term) {
                        $w->where('nom', 'ILIKE', "%{$term}%")
                          ->orWhere('prenom', 'ILIKE', "%{$term}%");
                    });
                })
                ->when($request->filled('email'), function ($q) use ($request) {
                    $q->where('email', 'ILIKE', '%' . $request->query('email') . '%');
                })
                ->when($request->filled('poste'), function ($q) use ($request) {
                    $term = $request->query('poste');
                    $q->whereHas('poste', fn ($poste) => $poste->where('nom', 'ILIKE', "%{$term}%"));
                })
                ->when($request->filled('categorie'), function ($q) use ($request) {
                    $term = $request->query('categorie');
                    $q->whereHas('poste', fn ($poste) => $poste->where('categorie', 'ILIKE', "%{$term}%"));
                })
                ->when($request->filled('departement'), function ($q) use ($request) {
                    $term = $request->query('departement');
                    $q->whereHas('departement', fn ($departement) => $departement->where('nom', 'ILIKE', "%{$term}%"));
                })
                ->when($activeOnly, function ($q) use ($now) {
                    $q->whereHas('contrats', function ($c) use ($now) {
                        $c->whereDate('date_debut', '<=', $now)
                          ->where(function ($w) use ($now) {
                              $w->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
                        });
                    });
                })
                ->when($request->filled('date_debut'), function ($q) use ($request) {
                    $q->whereDate('date_embauche', '>=', $request->query('date_debut'));
                })
                ->when($request->filled('date_fin'), function ($q) use ($request) {
                    $q->whereDate('date_embauche', '<=', $request->query('date_fin'));
                })
                ->orderBy($orderColumn, $orderDirection);

            $employes = $all ? $query->limit($limit)->get() : $query->paginate($perPage);

            return response()->json($employes);
        } catch (\Throwable $e) {
            Log::error('Erreur liste employes', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function store(EmployeRequest $request, SupabaseStorageService $storage)
    {
        try {
            $payload = $request->validated();
            $payload = $this->uploadPhotoSiNecessaire($payload, $storage);

            // Génération matricule si absent
            if (empty($payload['matricule'])) {
                $payload['matricule'] = $this->genererMatricule();
            }

            // Récupère département depuis le poste si non fourni
            if (empty($payload['departement_id']) && !empty($payload['poste_id'])) {
                $poste = Poste::find($payload['poste_id']);
                $payload['departement_id'] = $poste?->departement_id;
            }

            $employe = Employe::create($payload);

            // Créer automatiquement un compte utilisateur associé (rôle employé)
            $this->creerUserPourEmploye($employe);

            // Créditer les soldes de congés (accrual) dès la création
            try {
                app(SoldeCongeController::class)->accrue();
            } catch (\Throwable $e) {
                Log::warning('Accrual soldes après création employé a échoué', ['error' => $e->getMessage()]);
            }
            return response()->json($employe, 201);
        } catch (\Throwable $e) {
            Log::error('Erreur creation employe', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function show($id)
    {
        try {
            return Employe::with(['poste', 'departement', 'contrats', 'documents'])->findOrFail($id);
        } catch (\Throwable $e) {
            Log::error('Erreur show employe', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function update(EmployeRequest $request, $id, SupabaseStorageService $storage)
    {
        try {
            $employe = Employe::findOrFail($id);
            $payload = $request->validated();
            $payload = $this->uploadPhotoSiNecessaire($payload, $storage, $employe->photo);

            $ancienPoste = $employe->poste_id;
            $ancienDepartement = $employe->departement_id;

            // Si matricule non fourni lors d'un update, conserver l'ancien
            if (empty($payload['matricule'])) {
                unset($payload['matricule']);
            }

            // Si on change de poste, caler le département automatiquement si non passé
            if (array_key_exists('poste_id', $payload) && !array_key_exists('departement_id', $payload)) {
                $poste = Poste::find($payload['poste_id']);
                $payload['departement_id'] = $poste?->departement_id;
            }

            $employe->update($payload);

            if (array_key_exists('poste_id', $payload) && $payload['poste_id'] !== $ancienPoste) {
                $employe->ajouterChangementPoste(
                    $payload['poste_id'],
                    $payload['departement_id'] ?? $ancienDepartement,
                    'Changement de poste automatique'
                );
            }

            return response()->json($employe);
        } catch (\Throwable $e) {
            Log::error('Erreur update employe', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Employe::findOrFail($id)->delete();
            return response()->json(['message' => 'Employé supprimé']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression employe', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    protected function genererMatricule(): string
    {
        $prefix = 'EMP';
        $datePart = now()->format('Ymd');
        do {
            $rand = mt_rand(1000, 9999);
            $mat = "{$prefix}-{$datePart}-{$rand}";
        } while (Employe::where('matricule', $mat)->exists());

        return $mat;
    }

    private function uploadPhotoSiNecessaire(array $payload, SupabaseStorageService $storage, ?string $anciennePhoto = null): array
    {
        if (empty($payload['photo']) || !is_string($payload['photo']) || !str_starts_with($payload['photo'], 'data:image/')) {
            return $payload;
        }

        if ($anciennePhoto) {
            $storage->delete($anciennePhoto);
        }

        $path = $storage->uploadDataUrl($payload['photo'], 'employes');
        $payload['photo'] = $storage->publicUrl($path);

        return $payload;
    }

    /**
     * Crée un user associé à l'employé si non existant.
     */
    private function creerUserPourEmploye(Employe $employe): void
    {
        try {
            if (!$employe->email) {
                return;
            }

            if (User::where('email', $employe->email)->exists()) {
                return;
            }

            $password = env('DEFAULT_USER_PASSWORD', 'password');
            $email = $employe->email;
            if (User::where('email', $email)->exists()) {
                $email = Str::replace('@', '+' . Str::random(4) . '@', $email);
            }

            User::create([
                'name' => trim($employe->nom . ' ' . $employe->prenom),
                'email' => $email,
                'password' => $password, // AuthController compare en clair
                'role' => 'employe',
                'employe_id' => $employe->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Création user employé échouée', ['employe_id' => $employe->id, 'error' => $e->getMessage()]);
        }
    }
}
