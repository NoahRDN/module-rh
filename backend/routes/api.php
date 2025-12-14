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
use App\Http\Controllers\Api\EmployePdfController;
use App\Http\Controllers\Api\CalendrierEvenementController;
use App\Http\Controllers\Api\AlerteController;
use App\Http\Controllers\Api\AlerteSettingController;
use App\Http\Controllers\Api\ContratHistoriqueController;
use App\Http\Controllers\Api\ContratPdfController;
use App\Http\Controllers\Api\FrequenceCongeController;
use App\Http\Controllers\Api\TypeCongeController;
use App\Http\Controllers\Api\WorktimeSettingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\CritereEvaluationController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::group(['prefix' => 'employes'], function()
{
    Route::get('/', [EmployeController::class, 'liste_employe']);
    Route::get('/{id}/fiche_employe', [EmployeController::class, 'fiche_actuelle']);
    Route::get('/{id}/historique_poste', [EmployeController::class, 'historique_poste_employe']);
});

Route::prefix('v1')->group(function () {
    Route::apiResource('departements', DepartementController::class);
    Route::apiResource('postes', PosteController::class);
    // Types de congés accessibles en lecture sans auth stricte
    Route::get('types-conges', [TypeCongeController::class, 'index']);
    Route::get('frequences-conges', [FrequenceCongeController::class, 'index']);
    Route::apiResource('historiques-postes', HistoriquePosteController::class)->only(['index', 'store', 'show', 'destroy']);

    Route::middleware(['auth:sanctum', 'role:admin,rh'])->group(function () {
    Route::apiResource('employes', ApiEmployeController::class);
    Route::get('employes/{id}/pdf', [EmployePdfController::class, 'telecharger']);
    Route::apiResource('contrats', ContratController::class);
    Route::apiResource('contrats-historiques', ContratHistoriqueController::class)->only(['index']);
    Route::get('contrats/{id}/pdf', [ContratPdfController::class, 'telecharger']);
    Route::apiResource('documents', DocumentEmployeController::class);
    Route::get('documents/types', [DocumentUploadController::class, 'types']);
    Route::post('documents/upload', [DocumentUploadController::class, 'store']);
    Route::apiResource('soldes-conges', SoldeCongeController::class)->only(['index','show']);
    Route::get('worktime', [WorktimeSettingController::class, 'show']);
    Route::put('worktime', [WorktimeSettingController::class, 'update']);
    Route::apiResource('demandes-conges', DemandeCongeController::class);
    Route::apiResource('calendrier-evenements', CalendrierEvenementController::class)->only(['index', 'store']);
    Route::get('alertes', [AlerteController::class, 'index']);
    Route::post('demandes-conges/{id}/manager-approve', [DemandeCongeController::class, 'approveByManager']);
    Route::post('demandes-conges/{id}/rh-approve', [DemandeCongeController::class, 'approveByRH']);
    Route::post('demandes-conges/{id}/reject', [DemandeCongeController::class, 'reject']);
    Route::apiResource('pointages', PointageController::class)->only(['index', 'store']);
    Route::get('pointages/releve-journalier', [PointageController::class, 'releveJournalier']);
    Route::get('pointages/releve-mensuel', [PointageController::class, 'releveMensuel']);
    Route::get('pointages/releve-paie', [PointageController::class, 'relevePaie']);
    Route::post('paies/generer', [PaieController::class, 'genererPaie']);
    Route::get('paie-parametres', [PaieParametreController::class, 'index']);
    Route::put('paie-parametres/{id}', [PaieParametreController::class, 'update']);
    Route::get('paies/{id}/pdf', [PaiePdfController::class, 'telecharger']);
    
    // Dashboard et statistiques RH
    Route::get('dashboard/statistiques', [DashboardController::class, 'statistiques']);
    Route::get('dashboard/alertes', [DashboardController::class, 'alertes']);
    Route::get('dashboard/top-performers', [DashboardController::class, 'topPerformers']);
    
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
});
});
