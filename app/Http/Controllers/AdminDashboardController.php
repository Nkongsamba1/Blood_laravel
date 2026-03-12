<?php

namespace App\Http\Controllers;

use App\Models\Don;
use App\Models\Donneur;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Stats Globales & Alertes
     */
    public function getGlobalStats()
    {
        try {
            $totalDonneurs = Donneur::count();
            $volumeTotalMl = Don::where('statut', 'Effectué')->sum('quantite');
            $personnelActif = User::whereIn('role_utilisateur', ['admin', 'personnel'])->count();

            // Calcul des alertes (Seuil : 2000ml)
            $groupes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
            $alertes = [];

            foreach ($groupes as $groupe) {
                $vol = Don::whereHas('donneur', function($q) use ($groupe) {
                    $q->where('groupe_sanguin', $groupe);
                })->where('statut', 'Effectué')->sum('quantite');

                if ($vol < 2000) {
                    $alertes[] = [
                        'groupe_sanguin' => $groupe,
                        'total' => $vol,
                        'total_poches' => Don::whereHas('donneur', function($q) use ($groupe) {
                                            $q->where('groupe_sanguin', $groupe);
                                          })->where('statut', 'Effectué')->count()
                    ];
                }
            }

            return response()->json([
                'total_donneurs' => $totalDonneurs,
                'volume_total_litres' => round($volumeTotalMl / 1000, 2),
                'personnel_actif' => $personnelActif,
                'alertes_stock' => $alertes,
                // On récupère aussi la liste du staff ici pour éviter un 2ème appel
                'staff' => User::whereIn('role_utilisateur', ['admin', 'personnel'])->get()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Détails des stocks pour le graphique (ml + poches)
     */
    public function getStocks()
    {
        $groupes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $details = [];

        foreach ($groupes as $groupe) {
            $volume = Don::whereHas('donneur', function($q) use ($groupe) {
                $q->where('groupe_sanguin', $groupe);
            })->where('statut', 'Effectué')->sum('quantite');

            $poches = Don::whereHas('donneur', function($q) use ($groupe) {
                $q->where('groupe_sanguin', $groupe);
            })->where('statut', 'Effectué')->count();

            $details[] = [
                'groupe_sanguin' => $groupe,
                'total_ml' => (int)$volume,
                'total_poches' => $poches
            ];
        }

        return response()->json($details);
    }
    
}
