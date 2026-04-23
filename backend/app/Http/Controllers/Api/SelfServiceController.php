<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use App\Models\Demande;
use App\Models\SoldeConge;
use App\Models\DemandeConge;
use App\Models\Paie;
use App\Http\Controllers\Api\PaiePdfController;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\DocumentEmploye;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class SelfServiceController extends Controller
{
    /**
     * Récupérer les informations de l'employé connecté
     */
    public function monProfil(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $employe = Employe::with(['poste', 'departement', 'contrats' => function($q) {
                $q->orderBy('date_debut', 'desc');
            }])->findOrFail($user->employe_id);

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'employe' => $employe,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération profil', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mettre à jour les informations personnelles
     */
    public function mettreAJourProfil(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $employe = Employe::findOrFail($user->employe_id);

            // Champs modifiables par l'employé
            $validated = $request->validate([
                'telephone' => 'nullable|string|max:20',
                'adresse' => 'nullable|string|max:255',
                'photo' => 'nullable|string',
            ]);

            $employe->update($validated);

            return response()->json([
                'message' => 'Profil mis à jour',
                'employe' => $employe,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour profil', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Changer le mot de passe
     */
    public function changerMotDePasse(Request $request)
    {
        try {
            $validated = $request->validate([
                'mot_de_passe_actuel' => 'required|string',
                'nouveau_mot_de_passe' => 'required|string|min:8|confirmed',
            ]);

            $user = $request->user();

            // Vérifier l'ancien mot de passe avec Hash::check
            if (!Hash::check($validated['mot_de_passe_actuel'], $user->password)) {
                return response()->json(['message' => 'Mot de passe actuel incorrect'], 422);
            }

            $user->password = Hash::make($validated['nouveau_mot_de_passe']);
            $user->save();

            return response()->json(['message' => 'Mot de passe modifié avec succès']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur changement mot de passe', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Consulter les bulletins de paie
     */
    public function mesBulletins(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $query = Paie::where('employe_id', $user->employe_id)
                ->orderBy('periode', 'desc');

            if ($request->filled('annee')) {
                $query->where('annee', $request->input('annee'));
            }

            $bulletins = $query->get();

            return response()->json($bulletins);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération bulletins', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Consulter le solde de congés
     */
    public function monSoldeConges(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            // Récupérer le solde depuis la vue ou calculer
            $soldes = \DB::table('view_solde_conges')
                ->where('employe_id', $user->employe_id)
                ->get();

            return response()->json($soldes);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération solde congés', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Historique des demandes de congés
     */
    public function mesDemandesConges(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $demandes = DemandeConge::with('typeConge')
                ->where('employe_id', $user->employe_id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($demandes);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération demandes congés', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Télécharger un bulletin PDF (self-service)
     */
    public function telechargerBulletin($id, Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $paie = Paie::where('employe_id', $user->employe_id)->findOrFail($id);

            // Reutiliser le contrôleur PDF existant après vérification propriétaire
            $pdfController = app(PaiePdfController::class);
            return $pdfController->telecharger($paie->id);
        } catch (\Throwable $e) {
            Log::error('Erreur téléchargement bulletin self-service', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Créer une demande de congé
     */
    public function creerDemandeConge(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $validated = $request->validate([
                'type_conge_id' => 'required|exists:types_conges,id',
                // Autoriser les dates passées pour régularisation, on ne garde que la cohérence début/fin
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after_or_equal:date_debut',
                'motif' => 'nullable|string',
            ]);

            // Calculer le nombre de jours
            $dateDebut = \Carbon\Carbon::parse($validated['date_debut']);
            $dateFin = \Carbon\Carbon::parse($validated['date_fin']);
            $jours = $dateDebut->diffInDays($dateFin) + 1;

            $demande = DemandeConge::create([
                'employe_id' => $user->employe_id,
                'type_conge_id' => $validated['type_conge_id'],
                'date_debut' => $validated['date_debut'],
                'date_fin' => $validated['date_fin'],
                'jours_demandes' => $jours,
                'motif' => $validated['motif'],
                'statut' => 'en_attente',
            ]);

            // Notifier le manager/RH
            $this->notifierNouvelleDemandeConge($demande);

            $demande->load('typeConge');

            return response()->json($demande, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur création demande congé', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mes demandes (attestations, remboursements, etc.)
     */
    public function mesDemandes(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $demandes = Demande::with(['typeDemande', 'documents'])
                ->where('employe_id', $user->employe_id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($demandes);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération demandes', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Créer une demande (attestation, remboursement, etc.)
     */
    public function creerDemande(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $validated = $request->validate([
                'type_demande_id' => 'required|exists:types_demandes,id',
                'motif' => 'nullable|string',
                'donnees' => 'nullable|array',
                'montant' => 'nullable|numeric|min:0',
                'commentaire_employe' => 'nullable|string',
            ]);

            $validated['employe_id'] = $user->employe_id;

            $demande = Demande::create($validated);
            $demande->load('typeDemande');

            return response()->json($demande, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur création demande', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mes conversations avec le service RH
     */
    public function mesConversations(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $conversations = Conversation::with(['assigneA', 'dernierMessage'])
                ->where('employe_id', $user->employe_id)
                ->orderBy('derniere_activite', 'desc')
                ->get();

            return response()->json($conversations);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération conversations', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mes compétences
     */
    public function mesCompetences(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $employe = Employe::with(['competences.categorie'])->findOrFail($user->employe_id);

            $competences = $employe->competences->map(function($competence) {
                return [
                    'id' => $competence->id,
                    'code' => $competence->code,
                    'nom' => $competence->nom,
                    'categorie' => $competence->categorie->nom,
                    'niveau' => $competence->pivot->niveau,
                    'date_evaluation' => $competence->pivot->date_evaluation,
                ];
            });

            return response()->json($competences);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération compétences', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mes formations
     */
    public function mesFormations(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $formations = \App\Models\FormationEmploye::with('formation.competences')
                ->where('employe_id', $user->employe_id)
                ->orderBy('date_debut', 'desc')
                ->get();

            return response()->json($formations);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération formations', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mes documents personnels
     */
    public function mesDocuments(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $docs = DocumentEmploye::where('employe_id', $user->employe_id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($docs);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération documents', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Dashboard employé - Résumé
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->employe_id) {
                return response()->json(['message' => 'Profil employé non lié'], 403);
            }

            $employe = Employe::with(['poste', 'departement', 'competences.categorie'])->findOrFail($user->employe_id);

            // Notifications non lues
            $notifications = $user->notifications()->orderBy('created_at', 'desc')->take(10)->get();
            $notificationsNonLues = $user->notificationsNonLues()->count();

            // Demandes RH
            $demandes = Demande::with(['typeDemande'])
                ->where('employe_id', $user->employe_id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            $demandesEnCours = Demande::where('employe_id', $user->employe_id)
                ->whereIn('statut', ['soumise', 'en_cours'])
                ->count();

            // Congés
            $demandesConges = DemandeConge::with('typeConge')
                ->where('employe_id', $user->employe_id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            
            $congesEnAttente = DemandeConge::where('employe_id', $user->employe_id)
                ->whereIn('statut', ['en_attente', 'manager_valide'])
                ->count();

            // Solde congés
            $soldeConges = \DB::table('view_solde_conges')
                ->where('employe_id', $user->employe_id)
                ->first();

            // Conversations et messages
            $conversations = Conversation::with(['dernierMessage'])
                ->where('employe_id', $user->employe_id)
                ->orderBy('derniere_activite', 'desc')
                ->take(5)
                ->get();

            $messagesNonLus = \App\Models\Message::whereHas('conversation', function($q) use ($user) {
                $q->where('employe_id', $user->employe_id);
            })
                ->where('user_id', '!=', $user->id)
                ->where('lu', false)
                ->count();

            // Compétences
            $competences = $employe->competences->map(function($competence) {
                return [
                    'id' => $competence->id,
                    'nom' => $competence->nom,
                    'competence' => $competence,
                    'niveau' => $competence->pivot->niveau,
                    'date_evaluation' => $competence->pivot->date_evaluation,
                ];
            });

            // Formations
            $formations = \App\Models\FormationEmploye::with('formation')
                ->where('employe_id', $user->employe_id)
                ->orderBy('date_debut', 'desc')
                ->take(5)
                ->get();

            $formationsEnCours = \App\Models\FormationEmploye::where('employe_id', $user->employe_id)
                ->where('statut', 'en_cours')
                ->count();

            // Événements à venir
            $evenements = \App\Models\CalendrierEvenement::where(function($q) use ($user) {
                $q->whereNull('employe_id')->orWhere('employe_id', $user->employe_id);
            })
                ->where('date_debut', '>=', now())
                ->orderBy('date_debut')
                ->take(5)
                ->get();

            return response()->json([
                'profil' => [
                    'id' => $employe->id,
                    'nom' => $employe->nom,
                    'prenom' => $employe->prenom,
                    'matricule' => $employe->matricule,
                    'poste' => $employe->poste,
                    'departement' => $employe->departement,
                ],
                'solde_conges' => $soldeConges,
                'demandes' => $demandes,
                'demandes_conges' => $demandesConges,
                'competences' => $competences,
                'formations' => $formations,
                'notifications' => $notifications,
                'conversations' => $conversations,
                'evenements' => $evenements,
                'stats' => [
                    'notifications_non_lues' => $notificationsNonLues,
                    'demandes_en_cours' => $demandesEnCours,
                    'conges_en_attente' => $congesEnAttente,
                    'messages_non_lus' => $messagesNonLus,
                    'formations_en_cours' => $formationsEnCours,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur dashboard employé', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Notifier d'une nouvelle demande de congé
     */
    private function notifierNouvelleDemandeConge(DemandeConge $demande): void
    {
        $employe = Employe::find($demande->employe_id);
        $rhUsers = \App\Models\User::whereIn('role', ['rh', 'admin', 'manager'])->get();

        foreach ($rhUsers as $user) {
            Notification::creer(
                $user->id,
                'nouvelle_demande_conge',
                'Nouvelle demande de congé',
                sprintf(
                    '%s %s a demandé %d jour(s) de congé du %s au %s',
                    $employe->prenom,
                    $employe->nom,
                    $demande->jours_demandes,
                    $demande->date_debut->format('d/m/Y'),
                    $demande->date_fin->format('d/m/Y')
                ),
                ['demande_conge_id' => $demande->id]
            );
        }
    }
}
