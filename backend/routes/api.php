<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/**
 * Controller Personnalisé
 */
use App\Http\Controllers\EmployeController;

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