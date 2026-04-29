<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/**
 * Controller Personnalisé
 */
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\Api\EmployeController as ApiEmployeController;
use App\Http\Controllers\Api\DepartementController;
use App\Http\Controllers\Api\PosteController;
use App\Http\Controllers\Api\ContratController;
use App\Http\Controllers\Api\DocumentEmployeController;
use App\Http\Controllers\Api\HistoriquePosteController;
use App\Http\Controllers\Api\DocumentUploadController;
use App\Http\Controllers\Api\SoldeCongeController;
use App\Http\Controllers\Api\DemandeCongeController;
use App\Http\Controllers\Api\PointageController;
use App\Http\Controllers\Api\PaieController;
use App\Http\Controllers\Api\PaieParametreController;
use App\Http\Controllers\Api\PaiePdfController;
use App\Http\Controllers\Api\CaisseController;
use App\Http\Controllers\Api\IrsaTrancheController;
use App\Http\Controllers\Api\EmployePdfController;
use App\Http\Controllers\Api\CalendrierEvenementController;
use App\Http\Controllers\Api\AlerteController;
use App\Http\Controllers\Api\AlerteSettingController;
use App\Http\Controllers\Api\CategoriePosteController;
use App\Http\Controllers\Api\ContratHistoriqueController;
use App\Http\Controllers\Api\ContratPdfController;
use App\Http\Controllers\Api\FrequenceCongeController;
use App\Http\Controllers\Api\TypeCongeController;
use App\Http\Controllers\Api\WorktimeSettingController;
use App\Http\Controllers\Api\EntrepriseSettingController;
use App\Http\Controllers\Api\DeviseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\CritereEvaluationController;

// Nouveaux controllers pour compétences, formations, self-service
use App\Http\Controllers\Api\CategorieCompetenceController;
use App\Http\Controllers\Api\CompetenceController;
use App\Http\Controllers\Api\EmployeCompetenceController;
use App\Http\Controllers\Api\PosteCompetenceController;
use App\Http\Controllers\Api\FormationController;
use App\Http\Controllers\Api\FormationEmployeController;
use App\Http\Controllers\Api\MatchingController;
use App\Http\Controllers\Api\DemandeRHController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\MessagerieController;
use App\Http\Controllers\Api\SelfServiceController;

// Controllers pour Manager, Audit, Archives et Permissions
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RemunerationItemController;

// Nouveaux controllers IA et Automatisation
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\DocumentGeneratorController;
use App\Http\Controllers\Api\TurnoverPredictionController;
use App\Http\Controllers\Api\AnomalyDetectionController;
use App\Http\Controllers\Api\AIMatchingController;
use App\Http\Controllers\Api\JourFerieController;

Route::get('/debug-env', function () {
    return response()->json([
        'app' => [
            'APP_NAME_env' => env('APP_NAME'),
            'APP_NAME_config' => config('app.name'),
            'APP_ENV_env' => env('APP_ENV'),
            'APP_ENV_app' => app()->environment(),
            'APP_DEBUG_env' => env('APP_DEBUG'),
            'APP_DEBUG_config' => config('app.debug'),
            'APP_URL_env' => env('APP_URL'),
            'APP_URL_config' => config('app.url'),
        ],

        'database' => [
            'DB_CONNECTION_env' => env('DB_CONNECTION'),
            'DB_HOST_env' => env('DB_HOST'),
            'DB_DATABASE_env' => env('DB_DATABASE'),
            'DB_URL_SET' => !empty(env('DB_URL')),
            'DATABASE_URL_SET' => !empty(env('DATABASE_URL')),

            'default_connection_config' => config('database.default'),
            'pgsql_host_config' => config('database.connections.pgsql.host'),
            'pgsql_database_config' => config('database.connections.pgsql.database'),
            'pgsql_url_SET' => !empty(config('database.connections.pgsql.url')),
        ],
    ]);
});

Route::get('/debug-db', function () {
    return response()->json([
        'default' => config('database.default'),
        'pgsql_host' => config('database.connections.pgsql.host'),
        'pgsql_database' => config('database.connections.pgsql.database'),
        'pgsql_url_is_set' => !empty(config('database.connections.pgsql.url')),
        'env_db_host' => env('DB_HOST'),
        'env_db_url_is_set' => !empty(env('DB_URL')),
        'env_database_url_is_set' => !empty(env('DATABASE_URL')),
    ]);
});


Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Notifications (accessible à tous les utilisateurs authentifiés)
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/count', [NotificationController::class, 'countNonLues']);
        Route::post('/{id}/lue', [NotificationController::class, 'marquerLue']);
        Route::post('/lire-toutes', [NotificationController::class, 'marquerToutesLues']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/lues', [NotificationController::class, 'supprimerLues']);
    });
});

Route::group(['prefix' => 'employes'], function()
{
    Route::get('/', [EmployeController::class, 'liste_employe']);
    Route::get('/{id}/fiche_employe', [EmployeController::class, 'fiche_actuelle']);
    Route::get('/{id}/historique_poste', [EmployeController::class, 'historique_poste_employe']);
});

// Compatibilité : endpoints sans préfixe /v1 (ex: /api/postes, /api/departements)
Route::apiResource('postes', PosteController::class)->only(['index', 'show']);
Route::apiResource('departements', DepartementController::class)->only(['index', 'show']);

Route::prefix('v1')->group(function () {
    Route::apiResource('departements', DepartementController::class);
    Route::apiResource('postes', PosteController::class);
    Route::apiResource('categories-postes', CategoriePosteController::class);
    // Types de congés accessibles en lecture sans auth stricte
    Route::get('types-conges', [TypeCongeController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('types-conges', [TypeCongeController::class, 'store']);
        Route::get('types-conges/{id}', [TypeCongeController::class, 'show']);
        Route::match(['put', 'patch'], 'types-conges/{id}', [TypeCongeController::class, 'update']);
        Route::delete('types-conges/{id}', [TypeCongeController::class, 'destroy']);
    });
    Route::get('frequences-conges', [FrequenceCongeController::class, 'index']);
    Route::apiResource('historiques-postes', HistoriquePosteController::class)->only(['index', 'store', 'show', 'destroy']);
    
    // Compétences et formations (lecture publique pour certaines)
    Route::get('niveaux-competence', [CompetenceController::class, 'niveaux']);
    Route::get('categories-competences', [CategorieCompetenceController::class, 'index']);
    Route::get('competences', [CompetenceController::class, 'index']);
    Route::get('competences/cartographie', [CompetenceController::class, 'cartographie']);
    Route::get('formations', [FormationController::class, 'index']);
    Route::get('types-demandes', [DemandeRHController::class, 'types']);

    // ========================================
    // ROUTES SELF-SERVICE EMPLOYÉ
    // ========================================
    Route::middleware('auth:sanctum')->prefix('self-service')->group(function () {
        // Dashboard et profil
        Route::get('dashboard', [SelfServiceController::class, 'dashboard']);
        Route::get('profil', [SelfServiceController::class, 'monProfil']);
        Route::put('profil', [SelfServiceController::class, 'mettreAJourProfil']);
        Route::post('changer-mot-de-passe', [SelfServiceController::class, 'changerMotDePasse']);
        
    // Bulletins et congés
    Route::get('bulletins', [SelfServiceController::class, 'mesBulletins']);
    Route::get('bulletins/{id}/pdf', [SelfServiceController::class, 'telechargerBulletin']);
    Route::get('solde-conges', [SelfServiceController::class, 'monSoldeConges']);
    Route::get('demandes-conges', [SelfServiceController::class, 'mesDemandesConges']);
    Route::post('demandes-conges', [SelfServiceController::class, 'creerDemandeConge']);
        
        // Demandes (attestations, remboursements)
        Route::get('demandes', [SelfServiceController::class, 'mesDemandes']);
        Route::post('demandes', [SelfServiceController::class, 'creerDemande']);
        
        // Compétences et formations
        Route::get('competences', [SelfServiceController::class, 'mesCompetences']);
        Route::get('formations', [SelfServiceController::class, 'mesFormations']);
        Route::get('documents', [SelfServiceController::class, 'mesDocuments']);
        
        // Conversations/Messagerie
        Route::get('conversations', [SelfServiceController::class, 'mesConversations']);
        
        // Génération de documents self-service
        Route::post('documents/generer', [DocumentGeneratorController::class, 'selfServiceGenerer']);
    });

    Route::middleware('auth:sanctum')->get('documents/{document}/download', [DocumentEmployeController::class, 'download'])
        ->whereNumber('document');

    // ========================================
    // ROUTES MESSAGERIE (authentifié)
    // ========================================
    Route::middleware('auth:sanctum')->prefix('messagerie')->group(function () {
        Route::get('conversations', [MessagerieController::class, 'conversations']);
        Route::post('conversations', [MessagerieController::class, 'creerConversation']);
        Route::get('conversations/{id}', [MessagerieController::class, 'showConversation']);
        Route::post('conversations/{id}/messages', [MessagerieController::class, 'envoyerMessage']);
        Route::post('conversations/{id}/pieces-jointes', [MessagerieController::class, 'ajouterPieceJointe']);
        Route::post('conversations/{id}/lue', [MessagerieController::class, 'marquerLu']);
        Route::get('stats-non-lus', [MessagerieController::class, 'statsNonLus']);
    });

    // ========================================
    // CHATBOT IA (accessible à tous les authentifiés)
    // ========================================
    Route::middleware('auth:sanctum')->prefix('chatbot')->group(function () {
        Route::post('/ask', [ChatbotController::class, 'ask']);
        Route::get('/suggestions', [ChatbotController::class, 'suggestions']);
        Route::get('/historique', [ChatbotController::class, 'historique']);
    });

    Route::middleware(['auth:sanctum', 'role:admin,rh'])->group(function () {
    Route::apiResource('employes', ApiEmployeController::class);
    Route::get('employes/{id}/pdf', [EmployePdfController::class, 'telecharger']);
    Route::apiResource('contrats', ContratController::class);
    Route::apiResource('contrats-historiques', ContratHistoriqueController::class)->only(['index']);
    Route::get('contrats/{id}/pdf', [ContratPdfController::class, 'telecharger']);
    Route::get('documents/types', [DocumentUploadController::class, 'types']);
    Route::post('documents/upload', [DocumentUploadController::class, 'store']);
    Route::apiResource('documents', DocumentEmployeController::class)->whereNumber('document');
    Route::apiResource('remuneration-items', RemunerationItemController::class);
    Route::apiResource('soldes-conges', SoldeCongeController::class)->only(['index','show']);
    Route::get('worktime', [WorktimeSettingController::class, 'show']);
    Route::put('worktime', [WorktimeSettingController::class, 'update']);
    Route::get('entreprise-settings', [EntrepriseSettingController::class, 'show']);
    Route::post('entreprise-settings', [EntrepriseSettingController::class, 'update']);
    Route::apiResource('devises', DeviseController::class);
    Route::apiResource('demandes-conges', DemandeCongeController::class);
    Route::apiResource('calendrier-evenements', CalendrierEvenementController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('alertes', [AlerteController::class, 'index']);
    Route::post('demandes-conges/{id}/manager-approve', [DemandeCongeController::class, 'approveByManager']);
    Route::post('demandes-conges/{id}/rh-approve', [DemandeCongeController::class, 'approveByRH']);
    Route::post('demandes-conges/{id}/reject', [DemandeCongeController::class, 'reject']);
    Route::apiResource('pointages', PointageController::class)->only(['index', 'store']);
    Route::get('pointages/releve-journalier', [PointageController::class, 'releveJournalier']);
    Route::get('pointages/releve-mensuel', [PointageController::class, 'releveMensuel']);
    Route::get('pointages/releve-paie', [PointageController::class, 'relevePaie']);
    Route::post('paies/generer', [PaieController::class, 'genererPaie']);
    Route::get('paies/etat', [PaieController::class, 'etat']);
    Route::get('paies/suivi', [PaieController::class, 'suivi']);
    Route::get('paies/prevision', [PaieController::class, 'prevision']);
    Route::post('paies/{id}/annuler', [PaieController::class, 'annuler']);
    Route::post('paies/{id}/valider', [PaieController::class, 'valider']);
    Route::post('paies/{id}/payer', [PaieController::class, 'payer']);
    Route::get('paies/{id}', [PaieController::class, 'show']);
    Route::get('paies/{id}/recu-paiement', [PaieController::class, 'recuPaiement']);
    Route::get('paie-parametres', [PaieParametreController::class, 'index']);
    Route::put('paie-parametres/{id}', [PaieParametreController::class, 'update']);
    Route::apiResource('irsa-tranches', IrsaTrancheController::class)->only(['index','store','update','destroy']);
    Route::get('paies/{id}/pdf', [PaiePdfController::class, 'telecharger']);
    Route::get('caisses', [CaisseController::class, 'index']);
    Route::get('caisses/types', [CaisseController::class, 'types']);
    Route::get('caisses/en-attente-validation', [CaisseController::class, 'enAttente']);
    Route::post('caisses/mouvements', [CaisseController::class, 'storeMouvement']);
    Route::patch('caisses/{id}/toggle-active', [CaisseController::class, 'toggleActive']);
    Route::post('caisses/mouvements/{id}/valider', [CaisseController::class, 'valider']);
    Route::post('caisses/mouvements/{id}/rejeter', [CaisseController::class, 'rejeter']);
    Route::apiResource('jours-feries', JourFerieController::class)->only(['index','store','update','destroy']);
    
    // Dashboard et statistiques RH
    Route::get('dashboard/statistiques', [DashboardController::class, 'statistiques']);
    Route::get('dashboard/alertes', [DashboardController::class, 'alertes']);
    Route::get('dashboard/top-performers', [DashboardController::class, 'topPerformers']);
    Route::prefix('manager')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'statistiques']);
        Route::apiResource('demandes-conges', DemandeCongeController::class)->only(['index', 'show', 'update']);
    });
    
    // Paramètres des alertes
    Route::get('alerte-settings', [AlerteSettingController::class, 'index']);
    Route::get('alerte-settings/{id}', [AlerteSettingController::class, 'show']);
    Route::put('alerte-settings/{id}', [AlerteSettingController::class, 'update']);
    Route::get('alerte-settings/code/{code}', [AlerteSettingController::class, 'getByCode']);
    
    // Évaluations de performance
    Route::apiResource('evaluations', EvaluationController::class);
    Route::get('evaluations/{id}/pdf', [EvaluationController::class, 'genererPdf']);
    Route::get('evaluations/employe/{employeId}/historique', [EvaluationController::class, 'historiqueEmploye']);
    Route::get('evaluations-statistiques', [EvaluationController::class, 'statistiques']);
    
    // Critères d'évaluation
    Route::apiResource('criteres-evaluation', CritereEvaluationController::class);
    Route::post('criteres-evaluation/reorder', [CritereEvaluationController::class, 'reorder']);
    
    // ========================================
    // GESTION DES COMPÉTENCES (Admin/RH)
    // ========================================
    Route::apiResource('categories-competences', CategorieCompetenceController::class)->except(['index']);
    Route::apiResource('competences', CompetenceController::class)->except(['index']);
    
    // Compétences des employés
    Route::get('employes/{employeId}/competences', [EmployeCompetenceController::class, 'index']);
    Route::post('employes/{employeId}/competences', [EmployeCompetenceController::class, 'store']);
    Route::put('employes/{employeId}/competences', [EmployeCompetenceController::class, 'bulkUpdate']);
    Route::delete('employes/{employeId}/competences/{competenceId}', [EmployeCompetenceController::class, 'destroy']);
    Route::get('employes/{employeId}/competences/radar', [EmployeCompetenceController::class, 'radar']);
    
    // Compétences requises des postes
    Route::get('postes/{posteId}/competences', [PosteCompetenceController::class, 'index']);
    Route::post('postes/{posteId}/competences', [PosteCompetenceController::class, 'store']);
    Route::put('postes/{posteId}/competences', [PosteCompetenceController::class, 'bulkUpdate']);
    Route::delete('postes/{posteId}/competences/{competenceId}', [PosteCompetenceController::class, 'destroy']);
    
    // ========================================
    // GESTION DES FORMATIONS (Admin/RH)
    // ========================================
    Route::apiResource('formations', FormationController::class)->except(['index']);
    Route::apiResource('formation-employes', FormationEmployeController::class);
    Route::get('employes/{employeId}/formations', [FormationEmployeController::class, 'historiqueEmploye']);
    
    // ========================================
    // MATCHING ET SUGGESTIONS (Admin/RH)
    // ========================================
    Route::prefix('matching')->group(function () {
        Route::post('compatibilite', [MatchingController::class, 'compatibilite']);
        Route::get('postes/{posteId}/candidats', [MatchingController::class, 'candidatsPourPoste']);
        Route::get('employes/{employeId}/postes-compatibles', [MatchingController::class, 'postesCompatibles']);
        Route::get('employes/{employeId}/suggestions-formations', [MatchingController::class, 'suggestionsFormations']);
        Route::get('analyse-globale', [MatchingController::class, 'analyseGlobale']);
    });
    
    // ========================================
    // DEMANDES RH (attestations, remboursements)
    // ========================================
    Route::prefix('demandes-rh')->group(function () {
        Route::get('/', [DemandeRHController::class, 'index']);
        Route::post('/', [DemandeRHController::class, 'store']);
        Route::get('/{id}', [DemandeRHController::class, 'show']);
        Route::put('/{id}', [DemandeRHController::class, 'update']);
        Route::post('/{id}/soumettre', [DemandeRHController::class, 'soumettre']);
        Route::post('/{id}/approuver', [DemandeRHController::class, 'approuver']);
        Route::post('/{id}/rejeter', [DemandeRHController::class, 'rejeter']);
        Route::post('/{id}/annuler', [DemandeRHController::class, 'annuler']);
        Route::post('/{id}/documents', [DemandeRHController::class, 'ajouterDocument']);
        Route::delete('/{demandeId}/documents/{documentId}', [DemandeRHController::class, 'supprimerDocument']);
    });
    
    // ========================================
    // MESSAGERIE RH (gestion conversations)
    // ========================================
    Route::prefix('messagerie-rh')->group(function () {
        Route::post('conversations/{id}/assigner', [MessagerieController::class, 'assigner']);
        Route::put('conversations/{id}/statut', [MessagerieController::class, 'changerStatut']);
    });

    // ========================================
    // AUDIT (Admin/RH uniquement)
    // ========================================
    Route::prefix('audit')->group(function () {
        Route::get('/', [AuditController::class, 'index']);
        Route::get('/statistiques', [AuditController::class, 'statistiques']);
        Route::get('/actions', [AuditController::class, 'actions']);
        Route::get('/types', [AuditController::class, 'types']);
        Route::get('/users', [AuditController::class, 'users']);
        Route::get('/export', [AuditController::class, 'export']);
        Route::get('/entity-history', [AuditController::class, 'entityHistory']);
        Route::get('/{id}', [AuditController::class, 'show']);
    });

    // ========================================
    // ARCHIVES (Admin/RH uniquement)
    // ========================================
    Route::prefix('archives')->group(function () {
        // Paramètres de rétention
        Route::get('/settings', [ArchiveController::class, 'settings']);
        Route::get('/settings/{id}', [ArchiveController::class, 'showSetting']);
        Route::put('/settings/{id}', [ArchiveController::class, 'updateSetting']);
        
        // Documents archivés
        Route::get('/', [ArchiveController::class, 'index']);
        Route::post('/', [ArchiveController::class, 'store']);
        Route::get('/types', [ArchiveController::class, 'types']);
        Route::get('/statistiques', [ArchiveController::class, 'statistiques']);
        Route::get('/expiring', [ArchiveController::class, 'expiringDocuments']);
        Route::get('/expired', [ArchiveController::class, 'expiredDocuments']);
        Route::post('/process-expired', [ArchiveController::class, 'processExpired']);
        Route::post('/verify-integrity', [ArchiveController::class, 'verifyIntegrity']);
        Route::get('/{id}', [ArchiveController::class, 'show']);
        Route::get('/{id}/download', [ArchiveController::class, 'download']);
        Route::post('/{id}/note', [ArchiveController::class, 'addNote']);
    });

    // ========================================
    // PERMISSIONS (Admin uniquement)
    // ========================================
    Route::middleware(['role:admin'])->prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::get('/grouped', [PermissionController::class, 'grouped']);
        Route::get('/roles', [PermissionController::class, 'roles']);
        Route::get('/matrix', [PermissionController::class, 'matrix']);
        Route::get('/role/{role}', [PermissionController::class, 'forRole']);
        Route::put('/role/{role}', [PermissionController::class, 'updateRole']);
        Route::post('/check', [PermissionController::class, 'check']);
    });

    // ========================================
    // GÉNÉRATION DE DOCUMENTS (Admin/RH)
    // ========================================
    Route::prefix('documents-generator')->group(function () {
        Route::get('/types', [DocumentGeneratorController::class, 'types']);
        Route::post('/generer', [DocumentGeneratorController::class, 'generer']);
        Route::get('/employes/{employeId}/attestation-travail', [DocumentGeneratorController::class, 'attestationTravail']);
        Route::get('/employes/{employeId}/certificat-travail', [DocumentGeneratorController::class, 'certificatTravail']);
        Route::get('/employes/{employeId}/attestation-salaire', [DocumentGeneratorController::class, 'attestationSalaire']);
        Route::get('/employes/{employeId}/lettre-recommandation', [DocumentGeneratorController::class, 'lettreRecommandation']);
        Route::get('/employes/{employeId}/formations/{formationId}/attestation', [DocumentGeneratorController::class, 'attestationFormation']);
        Route::get('/demandes-conges/{demandeId}/attestation', [DocumentGeneratorController::class, 'attestationConge']);
        Route::get('/contrats/{contratId}/pdf', [DocumentGeneratorController::class, 'contratTravail']);
        Route::post('/contrats/{contratId}/avenant', [DocumentGeneratorController::class, 'avenantContrat']);
    });

    // ========================================
    // PRÉDICTION TURNOVER (Admin/RH)
    // ========================================
    Route::prefix('turnover')->group(function () {
        Route::get('/', [TurnoverPredictionController::class, 'index']);
        Route::get('/statistiques', [TurnoverPredictionController::class, 'statistiques']);
        Route::get('/top-risques', [TurnoverPredictionController::class, 'topRisques']);
        Route::get('/alertes', [TurnoverPredictionController::class, 'alertes']);
        Route::get('/departements', [TurnoverPredictionController::class, 'parDepartement']);
        Route::get('/tendances', [TurnoverPredictionController::class, 'tendances']);
        Route::get('/employes/{employeId}', [TurnoverPredictionController::class, 'show']);
    });

    // ========================================
    // DÉTECTION D'ANOMALIES (Admin/RH)
    // ========================================
    Route::prefix('anomalies')->group(function () {
        Route::get('/', [AnomalyDetectionController::class, 'index']);
        Route::get('/dashboard', [AnomalyDetectionController::class, 'dashboard']);
        Route::get('/statistiques', [AnomalyDetectionController::class, 'statistiques']);
        Route::get('/critiques', [AnomalyDetectionController::class, 'alertesCritiques']);
        Route::get('/pointage', [AnomalyDetectionController::class, 'pointage']);
        Route::get('/paie', [AnomalyDetectionController::class, 'paie']);
        Route::get('/conges', [AnomalyDetectionController::class, 'conges']);
        Route::get('/contrats', [AnomalyDetectionController::class, 'contrats']);
        Route::get('/heures', [AnomalyDetectionController::class, 'heures']);
    });

    // ========================================
    // MATCHING IA (Admin/RH)
    // ========================================
    Route::prefix('matching-ia')->group(function () {
        Route::post('/analyser-profil', [AIMatchingController::class, 'analyserProfil']);
        Route::post('/analyser-cv', [AIMatchingController::class, 'analyserCV']);
        Route::get('/employes/{employeId}/suggestions-formations', [AIMatchingController::class, 'suggestionsFormations']);
        Route::get('/employes/{employeId}/plan-carriere', [AIMatchingController::class, 'planCarriere']);
        Route::get('/postes/{posteId}/candidats', [AIMatchingController::class, 'candidatsPostAI']);
        Route::get('/employes/{employeId}/postes-compatibles', [AIMatchingController::class, 'postesCompatiblesAI']);
    });
});

    // ========================================
    // PORTAIL MANAGER (Managers, RH, Admin)
    // ========================================
    Route::middleware(['auth:sanctum', 'manager'])->prefix('v1/manager')->group(function () {
        // Dashboard
        Route::get('/dashboard', [ManagerController::class, 'dashboard']);
        
        // Équipe
        Route::get('/equipe', [ManagerController::class, 'equipe']);
        
        // Demandes de congés de l'équipe
        Route::get('/demandes-conges', [ManagerController::class, 'demandesConges']);
        Route::post('/demandes-conges/{id}/valider', [ManagerController::class, 'validerDemandeConge']);
        Route::post('/demandes-conges/{id}/rejeter', [ManagerController::class, 'rejeterDemandeConge']);
        
        // Demandes RH de l'équipe
        Route::get('/demandes-rh', [ManagerController::class, 'demandesRH']);
        Route::post('/demandes-rh/{id}/valider', [ManagerController::class, 'validerDemandeRH']);
        
        // Statistiques
        Route::get('/statistiques/absences', [ManagerController::class, 'statistiquesAbsences']);
        Route::get('/statistiques/performance', [ManagerController::class, 'statistiquesPerformance']);
        
        // Calendrier
        Route::get('/calendrier-absences', [ManagerController::class, 'calendrierAbsences']);
    });
});
