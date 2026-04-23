<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\CaisseMouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaisseController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'caisse_id' => 'nullable|exists:caisses,id',
            'type' => 'nullable|in:entree,sortie',
            'statut' => 'nullable|in:tous,en_attente_validation,valide,rejete',
            'active' => 'nullable|in:1,0,true,false',
        ]);

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
            ->when(!empty($validated['statut']) && $validated['statut'] !== 'tous', fn ($query) => $query->where('statut', $validated['statut']))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'caisses' => $caisses,
            'mouvements' => $mouvements,
        ]);
    }

    public function types()
    {
        return response()->json([
            'caisses' => Caisse::orderBy('nom')->get(),
        ]);
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
        $data = $request->validate([
            'caisse_id' => 'required|exists:caisses,id',
            'type' => 'required|in:entree,sortie',
            'montant' => 'required|numeric|min:0.01',
            'source' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            if (!Caisse::where('id', $data['caisse_id'])->where('active', true)->exists()) {
                return response()->json(['message' => 'Cette caisse est désactivée'], 422);
            }

            $mouvement = CaisseMouvement::create([
                ...$data,
                'statut' => 'en_attente_validation',
                'demande_validation_le' => now(),
            ]);

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
        $mouvements = CaisseMouvement::with([
                'caisse:id,nom,solde',
                'paie.employe:id,matricule,nom,prenom',
            ])
            ->where('statut', 'en_attente_validation')
            ->orderBy('created_at')
            ->get();

        return response()->json(['mouvements' => $mouvements]);
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
}
