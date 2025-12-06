<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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
        Route::apiResource('contrats', ContratController::class);
        Route::apiResource('documents', DocumentEmployeController::class);
        Route::post('documents/upload', [DocumentUploadController::class, 'store']);
    });
});
