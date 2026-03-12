<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DonneurController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\Api\CampagneController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Api\BloodStockController;

Route::get('/stocks', [BloodStockController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/donneur/dashboard', [DonneurController::class, 'getDashboardData']);
    Route::get('/campagnes/disponibles', [DonneurController::class, 'getCampagnes']);
    Route::get('/donneur/check-eligibilite', [DonneurController::class, 'checkEligibilite']);
    Route::post('/donneur/reserver', [DonneurController::class, 'reserverCampagne']);
    Route::delete('/donneur/reserver/{id}', [DonneurController::class, 'annulerReservation']);
    Route::put('/donneur/reserver/{id}', [DonneurController::class, 'modifierReservation']);
    Route::get('/donneur/historique', [DonneurController::class, 'getHistorique']);
    Route::get('/donneur/profil', [DonneurController::class, 'getProfil']);
    Route::put('/donneur/profil/update', [DonneurController::class, 'updateProfil']);
    Route::post('/donneur/profil/photo', [DonneurController::class, 'updateImage']);
    Route::put('/donneur/password/update', [DonneurController::class, 'updatePassword']);

    // Routes pour l'espace médical
    Route::get('/personnel/dashboard-stats', [PersonnelController::class, 'getDashboardStats']);
    Route::get('/personnel/chart-data', [PersonnelController::class, 'getChartData']);
    Route::get('/personnel/mouvements', [PersonnelController::class, 'getMouvementsRecents']);
    Route::get('/personnel/file-attente', [PersonnelController::class, 'getFileAttente']);
    Route::post('/personnel/valider-aptitude/{id}', [PersonnelController::class, 'validerAptitude']);
    Route::post('/personnel/finaliser-prelevement/{id}', [PersonnelController::class, 'finaliserPrelevement']);
    Route::get('/personnel/stocks', [PersonnelController::class, 'getStocks']);


    // --- LE SEUL CHANGEMENT POUR L'ADMIN (STOCK EN LITRES) ---
    Route::get('/admin/stocks', [AdminDashboardController::class, 'getStocks']);
    Route::get('/admin/stats-globales', [AdminDashboardController::class, 'getGlobalStats']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user-profile', [UserController::class, 'profile']);
    Route::post('/admin/profil/photo', [UserController::class, 'uploadPhoto']);
    Route::put('/admin/password/update', [UserController::class, 'updatePassword']);

    Route::put('/users/{id}', function (Request $request, $id) {
        $user = \App\Models\User::findOrFail($id);
        $user->update($request->only('nom_complet', 'email'));
        return response()->json(['user' => $user]);
    });
});

// Le reste de tes routes sans changement
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

Route::post('/campagnes', [CampagneController::class, 'store']);
Route::get('/campagnes', [CampagneController::class, 'index']);
Route::put('/campagnes/{id}', [CampagneController::class, 'update']);
Route::delete('/campagnes/{id}', [CampagneController::class, 'destroy']);

Route::get('/stocks', [StockController::class, 'index']);

Route::post('/inscription', [AuthController::class, 'register']);
Route::post('/connexion', [AuthController::class, 'login']);



Route::middleware('auth:sanctum')->group(function () {
    // Dashboard Admin
    Route::prefix('admin')->group(function () {
        Route::get('/stats-globales', [AdminDashboardController::class, 'getGlobalStats']);
        Route::get('/stocks', [AdminDashboardController::class, 'getStocks']);
    });

    // Profil (toujours nécessaire pour l'admin connecté)
    Route::get('/user-profile', [UserController::class, 'profile']);
});
