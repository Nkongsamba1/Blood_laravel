<?php

namespace App\Http\Controllers;

use App\Models\Don;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class PersonnelController extends Controller
{
    /**
     * Dashboard : Statistiques Globales
     */
public function getDashboardStats()
{
    try {
        $total = Don::count();
        $termines = Don::where('statut', 'Effectué')->count();
        $enAttente = Don::where('statut', 'En attente')->count();
        $annules = Don::where('statut', 'Annulé')->count();

        // Calcul du taux de réussite (Dons effectués / Total hors en attente)
        $tauxReussite = $total > 0 ? round(($termines / $total) * 100) : 0;

        return response()->json([
            'total_historique' => $total,
            'en_attente'       => $enAttente,
            'termines'         => $termines,
            'annules'          => $annules,
            'taux_reussite'    => $tauxReussite,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erreur de calcul des statistiques'], 500);
    }
}

    /**
     * Dashboard : Données du graphique d'évolution
     */
    public function getChartData()
    {
        try {
            $stats = Don::where('statut', 'Effectué')
                ->select(
                    DB::raw('DATE_FORMAT(updated_at, "%d %b") as date'),
                    DB::raw('count(*) as count')
                )
                ->groupBy('date')
                ->orderBy('updated_at', 'ASC')
                ->limit(10)
                ->get();

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur graphique'], 500);
        }
    }

    /**
     * Dashboard : Liste des 8 derniers mouvements
     */
    public function getMouvementsRecents()
    {
        try {
            // Eager loading des relations définies dans tes modèles
            return Don::with(['donneur.user'])
                ->where('statut', 'Effectué')
                ->orderBy('updated_at', 'desc')
                ->limit(8)
                ->get()
                ->map(function($don) {
                    return [
                        'id'     => $don->id,
                        'group'  => $don->donneur->groupe_sanguin ?? '?',
                        // On sécurise l'accès au nom de l'utilisateur
                        'nom'    => ($don->donneur && $don->donneur->user) ? $don->donneur->user->name : 'Donneur Inconnu',
                        'type'   => 'Don prélevé',
                        'date'   => $don->updated_at ? $don->updated_at->diffForHumans() : '...',
                        'volume' => $don->quantite ?? 0
                    ];
                });
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur mouvements'], 500);
        }
    }

public function getFileAttente()
{
    $liste = Don::with(['donneur.user'])
        ->whereDate('date_don', now()->toDateString()) // Utilise date_don
        ->whereIn('statut', ['En attente', 'Apte'])
        ->orderBy('heure_rdv', 'asc') // Tri par heure de rendez-vous
        ->get();

    $totalAttente = $liste->where('statut', 'En attente')->count();

    return response()->json([
        'liste' => $liste,
        'total_attente' => $totalAttente
    ]);
}

// Etape 1 : Valider l'aptitude médicale
    public function validerAptitude(Request $request, $id)
    {
        $don = Don::findOrFail($id);

        $don->update([
            'tension_arterielle' => $request->tension,
            'poids_donneur' => $request->poids,
            'statut' => $request->est_apte ? 'Apte' : 'Annulé'
        ]);

        return response()->json(['message' => 'Examen enregistré']);
    }

    // Etape 2 : Enregistrer le prélèvement final
    public function finaliserPrelevement(Request $request, $id)
    {
        $don = Don::findOrFail($id);

        $don->update([
            'num_poche' => $request->num_poche,
            'quantite' => $request->quantite,
            'heure_prelevement' => $request->heure_debut,
            'statut' => 'Effectué',
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Prélèvement terminé avec succès']);
    }

    public function getStocks()
{
    $groupes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $stocks = [];

    foreach ($groupes as $groupe) {
        // On calcule le volume total en ml pour chaque groupe
        $volumeMl = \App\Models\Don::whereHas('donneur', function($query) use ($groupe) {
            $query->where('groupe_sanguin', $groupe);
        })
        ->where('statut', 'Effectué')
        ->sum('quantite');

        // On définit un seuil critique (ex: moins de 2 litres)
        $status = ($volumeMl < 2000) ? 'Critique' : 'Stable';

        // Calcul du pourcentage basé sur une capacité max de 10L par groupe (ajustable)
        $percentage = min(round(($volumeMl / 10000) * 100), 100);

        $stocks[] = [
            'group' => $groupe,
            'liters' => $volumeMl,
            'status' => $status,
            'percentage' => $percentage
        ];
    }

    // Récupérer les 5 derniers mouvements (Entrées de stock)
    $recentMoves = \App\Models\Don::with(['donneur.user'])
        ->where('statut', 'Effectué')
        ->orderBy('updated_at', 'desc')
        ->take(5)
        ->get()
        ->map(function($don) {
            return [
                'id' => $don->id,
                'type' => 'Entrée',
                'destination' => $don->donneur->user->name,
                'date' => $don->updated_at->format('d M, H:i'),
                'volume' => $don->quantite,
                'group' => $don->donneur->groupe_sanguin
            ];
        });
        // Calcul de l'activité des 7 derniers jours
    $statsActivite = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = \Carbon\Carbon::today()->subDays($i);

        // On compte le nombre de dons 'Effectué' pour ce jour précis
        $count = \App\Models\Don::whereDate('updated_at', $date)
            ->where('statut', 'Effectué')
            ->count();

        // On normalise en pourcentage pour la hauteur du graphique (ex: max 20 dons = 100%)
        $statsActivite[] = min(($count / 20) * 100, 100);
    }

    return response()->json([
        'stocks' => $stocks, // tes stocks par groupe
        'recent_moves' => $recentMoves, // tes 5 derniers mouvements
        'chart_data' => $statsActivite // Le nouveau tableau dynamique [0, 15, 40, ...]
    ]);

}

    public function getMe(Request $request)
    {
        $user = $request->user();

        // Calcul des stats réelles depuis la base de données
        $stats = [
            'dons_valides' => Don::where('personnel_id', $user->id)->where('statut', 'Effectué')->count(),
            'examens_realises' => Don::where('personnel_id', $user->id)->count(),
            'heures_garde' => 120, // Exemple statique ou à calculer selon vos logs
        ];

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->nom_complet,
                'email' => $user->email,
                'telephone' => $user->telephone ?? 'Non renseigné',
                'photo' => $user->photo ? asset('storage/' . $user->photo) : null,
            ],
            'stats' => $stats
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'photo' => 'nullable|image|max:2048'
        ]);

        $user->nom_complet = $request->name;
        $user->email = $request->email;
        $user->telephone = $request->telephone;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $path = $request->file('photo')->store('profils', 'public');
            $user->photo = $path;
        }

        $user->save();

        return response()->json(['message' => 'Profil mis à jour avec succès', 'user' => $user]);
    }
}

