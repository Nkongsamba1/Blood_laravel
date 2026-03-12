<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Don;
use App\Models\Campagne;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
public function index()
{
    // 1. Statistiques des Donneurs & Progression
    $totalDonneurs = User::where('role_utilisateur', 'donneur')->count();
    $nouveauxCeMois = User::where('role_utilisateur', 'donneur')
        ->whereMonth('created_at', now()->month)
        ->count();

    $progression = $totalDonneurs > 0 ? round(($nouveauxCeMois / $totalDonneurs) * 100, 1) : 0;

    // 2. État des Stocks (CORRIGÉ)
    // On définit la liste des groupes pour être sûr qu'ils apparaissent tous
    $groupesSanguins = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    // On récupère les stocks réels
    $stocksReels = DB::table('donneurs')
        ->join('dons', 'donneurs.donneur_id', '=', 'dons.donneur_id')
        ->select('donneurs.groupe_sanguin', DB::raw('count(dons.id) as total_poches'))
        ->where('dons.statut', 'termine')
        ->groupBy('donneurs.groupe_sanguin')
        ->get()
        ->keyBy('groupe_sanguin'); // On indexe par groupe pour un accès facile

    // On s'assure que chaque groupe existe dans le retour, même avec 0
    $stocks = collect($groupesSanguins)->map(function($groupe) use ($stocksReels) {
        return [
            'groupe_sanguin' => $groupe,
            'total_poches' => isset($stocksReels[$groupe]) ? $stocksReels[$groupe]->total_poches : 0
        ];
    });

    // 3. Activité Récente
    $activite = Don::with(['donneur', 'personnel'])
        ->latest()
        ->take(5)
        ->get();

    // 4. Staff en service
    $staff = User::whereIn('role_utilisateur', ['admin', 'personnel'])
        ->latest()
        ->take(3)
        ->get();

    return response()->json([
        'donneurs' => [
            'total' => $totalDonneurs,
            'progression' => $progression,
            'tendance' => 'up'
        ],
        'stocks' => $stocks, // Contiendra maintenant les 8 groupes, même à 0
        'activite' => $activite,
        'staff' => $staff
    ]);
}
}
