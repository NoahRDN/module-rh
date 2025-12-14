<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Models\DemandeDocument;
use App\Models\TypeDemandeRH;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DemandeRHController extends Controller
{
    /**
     * Liste des demandes (pour RH)
     */
    public function index(Request $request)
    {
        try {
            $query = Demande::with(['employe', 'typeDemande', 'traitePar']);

            if ($request->has('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->has('type_demande_id')) {
                $query->where('type_demande_id', $request->type_demande_id);
            }

            if ($request->has('categorie')) {
                $query->whereHas('typeDemande', function($q) use ($request) {
                    $q->where('categorie', $request->categorie);
                });
            }

            if ($request->has('employe_id')) {
                $query->where('employe_id', $request->employe_id);
            }

            $demandes = $query->orderBy('created_at', 'desc')->get();

            return response()->json($demandes);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération demandes', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Types de demandes disponibles
     */
    public function types(Request $request)
    {
        try {
            $query = TypeDemandeRH::actif();

            if ($request->has('categorie')) {
                $query->where('categorie', $request->categorie);
            }

            $types = $query->get();

            return response()->json($types);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération types demandes', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Créer une nouvelle demande
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employe_id' => 'required|exists:employes,id',
                'type_demande_id' => 'required|exists:types_demandes,id',
                'motif' => 'nullable|string',
                'donnees' => 'nullable|array',
                'montant' => 'nullable|numeric|min:0',
                'commentaire_employe' => 'nullable|string',
            ]);

            $demande = Demande::create($validated);
            $demande->load(['employe', 'typeDemande']);

            return response()->json($demande, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur création demande', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Détail d'une demande
     */
    public function show($id)
    {
        try {
            $demande = Demande::with(['employe', 'typeDemande', 'traitePar', 'documents'])
                ->findOrFail($id);
            return response()->json($demande);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Demande non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération demande', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mettre à jour une demande (avant soumission)
     */
    public function update(Request $request, $id)
    {
        try {
            $demande = Demande::findOrFail($id);

            if ($demande->statut !== 'brouillon') {
                return response()->json(['message' => 'Seules les demandes en brouillon peuvent être modifiées'], 422);
            }

            $validated = $request->validate([
                'motif' => 'nullable|string',
                'donnees' => 'nullable|array',
                'montant' => 'nullable|numeric|min:0',
                'commentaire_employe' => 'nullable|string',
            ]);

            $demande->update($validated);
            $demande->load(['employe', 'typeDemande']);

            return response()->json($demande);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Demande non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour demande', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Soumettre une demande
     */
    public function soumettre($id)
    {
        try {
            $demande = Demande::with('typeDemande')->findOrFail($id);

            if ($demande->statut !== 'brouillon') {
                return response()->json(['message' => 'Cette demande a déjà été soumise'], 422);
            }

            // Vérifier si des documents sont requis
            if ($demande->typeDemande->necessite_document && $demande->documents()->count() === 0) {
                return response()->json(['message' => 'Cette demande nécessite au moins un document justificatif'], 422);
            }

            $demande->soumettre();

            // Notifier le service RH
            $this->notifierRH($demande);

            return response()->json(['message' => 'Demande soumise avec succès', 'demande' => $demande]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Demande non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur soumission demande', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Approuver une demande (RH)
     */
    public function approuver(Request $request, $id)
    {
        try {
            $demande = Demande::findOrFail($id);

            if (!in_array($demande->statut, ['soumise', 'en_cours'])) {
                return response()->json(['message' => 'Cette demande ne peut pas être approuvée'], 422);
            }

            $commentaire = $request->get('commentaire');
            $demande->approuver($request->user(), $commentaire);

            // Notifier l'employé
            $this->notifierEmploye($demande, 'approuvee');

            return response()->json(['message' => 'Demande approuvée', 'demande' => $demande]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Demande non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur approbation demande', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Rejeter une demande (RH)
     */
    public function rejeter(Request $request, $id)
    {
        try {
            $request->validate([
                'motif' => 'required|string|min:10',
            ]);

            $demande = Demande::findOrFail($id);

            if (!in_array($demande->statut, ['soumise', 'en_cours'])) {
                return response()->json(['message' => 'Cette demande ne peut pas être rejetée'], 422);
            }

            $demande->rejeter($request->user(), $request->motif);

            // Notifier l'employé
            $this->notifierEmploye($demande, 'rejetee');

            return response()->json(['message' => 'Demande rejetée', 'demande' => $demande]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Demande non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur rejet demande', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Ajouter un document à une demande
     */
    public function ajouterDocument(Request $request, $id)
    {
        try {
            $demande = Demande::findOrFail($id);

            if ($demande->statut !== 'brouillon') {
                return response()->json(['message' => 'Impossible d\'ajouter des documents à une demande soumise'], 422);
            }

            $request->validate([
                'document' => 'required|file|max:10240', // Max 10MB
            ]);

            $file = $request->file('document');
            $path = $file->store('demandes/' . $demande->id, 'public');

            $document = DemandeDocument::create([
                'demande_id' => $demande->id,
                'nom' => $file->getClientOriginalName(),
                'chemin' => $path,
                'type_mime' => $file->getMimeType(),
                'taille' => $file->getSize(),
            ]);

            return response()->json($document, 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Demande non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur ajout document', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Supprimer un document d'une demande
     */
    public function supprimerDocument($demandeId, $documentId)
    {
        try {
            $demande = Demande::findOrFail($demandeId);

            if ($demande->statut !== 'brouillon') {
                return response()->json(['message' => 'Impossible de supprimer des documents d\'une demande soumise'], 422);
            }

            $document = DemandeDocument::where('demande_id', $demandeId)
                ->where('id', $documentId)
                ->firstOrFail();

            Storage::disk('public')->delete($document->chemin);
            $document->delete();

            return response()->json(['message' => 'Document supprimé']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Ressource non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression document', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Annuler une demande (par l'employé)
     */
    public function annuler($id)
    {
        try {
            $demande = Demande::findOrFail($id);

            if (!in_array($demande->statut, ['brouillon', 'soumise'])) {
                return response()->json(['message' => 'Cette demande ne peut plus être annulée'], 422);
            }

            $demande->statut = 'annulee';
            $demande->save();

            return response()->json(['message' => 'Demande annulée', 'demande' => $demande]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Demande non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur annulation demande', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Notifier le service RH d'une nouvelle demande
     */
    private function notifierRH(Demande $demande): void
    {
        $rhUsers = \App\Models\User::whereIn('role', ['rh', 'admin'])->get();

        foreach ($rhUsers as $user) {
            Notification::creer(
                $user->id,
                'nouvelle_demande',
                'Nouvelle demande',
                sprintf(
                    '%s %s a soumis une demande de type "%s"',
                    $demande->employe->prenom,
                    $demande->employe->nom,
                    $demande->typeDemande->libelle
                ),
                ['demande_id' => $demande->id]
            );
        }
    }

    /**
     * Notifier l'employé du traitement de sa demande
     */
    private function notifierEmploye(Demande $demande, string $statut): void
    {
        $user = $demande->employe->user;
        if (!$user) {
            return;
        }

        $titre = $statut === 'approuvee' ? 'Demande approuvée' : 'Demande refusée';
        $message = sprintf(
            'Votre demande %s de type "%s" a été %s.',
            $demande->numero,
            $demande->typeDemande->libelle,
            $statut === 'approuvee' ? 'approuvée' : 'refusée'
        );

        Notification::creer(
            $user->id,
            'demande_' . $statut,
            $titre,
            $message,
            ['demande_id' => $demande->id]
        );
    }
}
