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
use App\Http\Controllers\Api\AbsenceTypeController;
use App\Http\Controllers\Api\SoldeCongeController;
use App\Http\Controllers\Api\DemandeCongeController;
use App\Http\Controllers\Api\PointageController;
use App\Http\Controllers\Api\PaieController;
use App\Http\Controllers\Api\PaieParametreController;
use App\Http\Controllers\Api\PaiePdfController;
use App\Http\Controllers\Api\EmployePdfController;
use App\Http\Controllers\Api\CalendrierEvenementController;
use App\Http\Controllers\Api\AlerteController;
use App\Http\Controllers\Api\ContratHistoriqueController;
use App\Http\Controllers\Api\ContratPdfController;

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
    Route::apiResource('absences-types', AbsenceTypeController::class);
    Route::apiResource('soldes-conges', SoldeCongeController::class);
    Route::post('soldes-conges/accrue', [SoldeCongeController::class, 'accrue']);
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
});
});
