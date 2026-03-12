<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Don;
use App\Models\Donneur;
use App\Models\Campagne;
use App\Models\User;
use Carbon\Carbon;

class DonneurController extends Controller
{
    /**
     * Récupère les données du Dashboard
     */
public function getDashboardData(Request $request)
{
    /** @var User $user */
    $user = Auth::user();

    if (!$user) {
        return response()->json(['error' => 'Non authentifié'], 401);
    }

    $donneur = $user->donneur;

    // Initialisation avec 'nom_complet' issu de ta base
    $data = [
        'nom_complet' => $user->nom_complet, // Correction ici
        'photo' => $user->photo ? asset('storage/' . $user->photo) : null,
        'groupe_sanguin' => $donneur ? $donneur->groupe_sanguin : '?',
        'delai' => 0,
        'historique' => [],
        'campagnes' => []
    ];

    // Récupération des CAMPAGNES (toutes, le scroll sera géré en CSS)
    $data['campagnes'] = \App\Models\Campagne::where('date_fin', '>=', now())
        ->orderBy('date_debut', 'asc')
        ->get()
        ->map(function($campagne) {
            $campagne->jours_restants = (int)now()->diffInDays($campagne->date_fin, false);
            return $campagne;
        });

    if ($donneur) {
        // 1. Calcul du délai
        $dernierDon = Don::where('donneur_id', $donneur->donneur_id)
            ->whereIn('statut', ['Effectué', 'Terminé', 'Validé'])
            ->latest('date_don')
            ->first();

        if ($dernierDon) {
            $dateProchainDon = \Carbon\Carbon::parse($dernierDon->date_don)->addDays(56);
            $reste = now()->diffInDays($dateProchainDon, false);
            $data['delai'] = $reste > 0 ? (int)$reste : 0;
        }

        // 2. Historique complet (le scroll gérera l'affichage)
        $data['historique'] = Don::where('donneur_id', $donneur->donneur_id)
            ->latest('date_don')
            ->get(['date_don', 'lieu', 'statut']);
    }

    return response()->json($data);
}

    /**
     * Liste des campagnes disponibles pour la réservation (Frontend)
     * Correction : utilise 'date_fin' pour filtrer les campagnes actives
     */
    public function getCampagnes()
    {
        try {
            $campagnes = Campagne::where('date_fin', '>=', now()->toDateString())
                ->orderBy('date_debut', 'asc')
                ->get();

            return response()->json($campagnes);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupère les informations du Profil
     */
    public function getProfil()
    {
        /** @var User $user */
        $user = Auth::user();
        $donneur = $user->donneur;

        return response()->json([
            'nom_complet'    => $user->nom_complet,
            'email'          => $user->email,
            'groupe_sanguin' => $donneur->groupe_sanguin ?? 'Non défini',
            'telephone'      => $donneur->telephone ?? 'Non renseigné',
            'genre'          => $donneur->genre ?? 'Non spécifié',
            'date_naissance' => $donneur->date_naissance ?? '',
            'photo'          => $user->photo ? asset('storage/' . $user->photo) : null
        ]);
    }

    /**
     * Mise à jour des informations personnelles
     */
    public function updateProfil(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $donneur = $user->donneur;

        $user->update([
            'nom_complet' => $request->nom_complet,
            'email' => $request->email,
        ]);

        if ($donneur) {
            $donneur->update([
                'telephone'      => $request->telephone,
                'date_naissance' => $request->date_naissance,
                'genre'          => $request->genre,
            ]);
        }

        return response()->json(['message' => 'Profil mis à jour']);
    }

    /**
     * Gestion du changement de mot de passe
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Ancien mot de passe incorrect.'], 403);
        }

        $user->update(['password' => Hash::make($request->new_password)]);
        return response()->json(['message' => 'Mot de passe modifié !']);
    }

    /**
     * Upload et mise à jour de la photo
     */
    public function updateImage(Request $request)
    {
        $request->validate(['photo' => 'required|image|max:2048']);

        /** @var User $user */
        $user = Auth::user();

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $path = $request->file('photo')->store('profiles', 'public');
            $user->update(['photo' => $path]);

            return response()->json(['photo_url' => asset('storage/' . $path)]);
        }
    }


    /**
     * Réservation d'une campagne de don
     * Correction : utilise 'date_debut' comme date de référence pour le don
     */
    public function reserverCampagne(Request $request)
    {
        $request->validate([
            'campagne_id' => 'required|exists:campagnes,id',
            'heure_rdv' => 'required'
        ]);

        /** @var User $user */
        $user = Auth::user();
        $donneur = $user->donneur;

        if (!$donneur) {
            return response()->json(['message' => 'Profil donneur introuvable'], 404);
        }

        $campagne = Campagne::findOrFail($request->campagne_id);

        // On vérifie si le donneur n'a pas déjà réservé pour cette campagne
        $dejaReserve = Don::where('donneur_id', $donneur->donneur_id)
            ->where('campagne_id', $campagne->id)
            ->exists();

        if ($dejaReserve) {
            return response()->json(['message' => 'Vous avez déjà une réservation pour cette campagne'], 422);
        }

        // Création du don avec statut 'En attente'
        $don = Don::create([
            'donneur_id' => $donneur->donneur_id,
            'campagne_id' => $campagne->id,
            'date_don' => $campagne->date_debut,
            'heure_rdv' => $request->heure_rdv,
            'lieu' => $campagne->lieu,
            'type_don' => 'Sang Total',
            'statut' => 'En attente'
        ]);

        return response()->json(['message' => 'Réservation réussie !', 'don' => $don], 201);
    }


    /**
 * Récupère l'historique complet des dons et réservations du donneur
 */
public function getHistorique()
{
    try {
        /** @var User $user */
        $user = Auth::user();
        $donneur = $user->donneur;

        if (!$donneur) {
            return response()->json([], 200);
        }

        // On récupère les dons avec les détails de la campagne liée
        $dons = Don::with('campagne')
            ->where('donneur_id', $donneur->donneur_id)
            ->orderBy('date_don', 'desc')
            ->get();

        return response()->json($dons);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


    // Supprimer une réservation
public function annulerReservation($id)
{
    $user = Auth::user();
    $donneur = $user->donneur;

    $don = Don::where('id', $id)->where('donneur_id', $donneur->donneur_id)->firstOrFail();

    if ($don->statut !== 'En attente') {
        return response()->json(['message' => 'Impossible d\'annuler un don déjà validé ou terminé.'], 422);
    }

    $don->delete();
    return response()->json(['message' => 'Réservation annulée avec succès.']);
}

// Modifier l'heure d'une réservation
public function modifierReservation(Request $request, $id)
{
    $request->validate(['heure_rdv' => 'required']);

    $user = Auth::user();
    $don = Don::where('id', $id)->where('donneur_id', $user->donneur->donneur_id)->firstOrFail();

    if ($don->statut !== 'En attente') {
        return response()->json(['message' => 'Modification impossible à ce stade.'], 422);
    }

    $don->update(['heure_rdv' => $request->heure_rdv]);
    return response()->json(['message' => 'Horaire mis à jour !']);
}

    public function checkEligibilite()
{
    $user = Auth::user();
    $donneur = $user->donneur;

    // IMPORTANT : On ne prend en compte que les dons avec le statut 'Effectué'
    // car c'est là que le prélèvement a eu lieu.
    $dernierDonEffectue = Don::where('donneur_id', $donneur->donneur_id)
        ->where('statut', 'Effectué')
        ->orderBy('date_don', 'desc')
        ->first();

    if (!$dernierDonEffectue) {
        return response()->json(['eligible' => true]);
    }

    $datePrelevement = \Carbon\Carbon::parse($dernierDonEffectue->date_don);
    $aujourdhui = \Carbon\Carbon::now();
    $joursDepuisPrelevement = $aujourdhui->diffInDays($datePrelevement);

    $delaiRequis = 56;
    $joursRestants = $delaiRequis - $joursDepuisPrelevement;

    if ($joursDepuisPrelevement < $delaiRequis) {
        return response()->json([
            'eligible' => false,
            'jours_ecoules' => $joursDepuisPrelevement,
            'jours_restants' => $joursRestants,
            'date_dernier_don' => $datePrelevement->format('d/m/Y')
        ]);
    }

    return response()->json(['eligible' => true]);
}
}
