<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Campagne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Notifications\NouvelleCampagneNotification;
use Illuminate\Support\Facades\Notification;

class CampagneController extends Controller
{
    /**
     * Enregistrer une nouvelle campagne
     */
public function store(Request $request)
{
    // 1. Validation des données
    $validator = Validator::make($request->all(), [
        'titre'          => 'required|string|max:255',
        'lieu'           => 'required|string|max:255',
        'date_debut'     => 'required|date',
        'date_fin'       => 'required|date|after_or_equal:date_debut',
        'objectif'       => 'required|integer|min:1',
        'planning'       => 'required|array',
        'message'        => 'nullable|string',
        'groupes_cibles' => 'nullable|array', // On valide que c'est bien un tableau
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    // 2. Création de la campagne dans la BDD
    $campagne = Campagne::create([
        'titre'          => $request->titre,
        'lieu'           => $request->lieu,
        'date_debut'     => $request->date_debut,
        'date_fin'       => $request->date_fin,
        'capacite_max'   => $request->objectif,
        'description'    => $request->message,
        'planning'       => $request->planning,
        'groupes_cibles' => $request->groupes_cibles,
    ]);

    // 3. Ciblage des utilisateurs via la relation 'donneur'
    // On cherche les "Users" qui ont un profil "Donneur" correspondant


    $query = User::query();

    if (!empty($request->groupes_cibles)) {
        // Correction ici : on passe par la relation 'donneur'
        $query->whereHas('donneur', function ($q) use ($request) {
            $q->whereIn('groupe_sanguin', $request->groupes_cibles);
        });
    } else {
        // Optionnel : Si aucun groupe n'est choisi, on cible tous les utilisateurs qui sont des donneurs
        $query->has('donneur');
    }

    $users = $query->get();

    // 4. Envoi de la notification
   // ... après avoir récupéré les $users

if ($users->count() > 0) {
    try {
        // Envoi réel
        Notification::send($users, new NouvelleCampagneNotification($campagne));
    } catch (\Exception $e) {
        // Si l'envoi échoue (pas d'internet, mauvais mot de passe SMTP)
        Log::error("Erreur d'envoi réel : " . $e->getMessage());
        return response()->json([
            'status' => 'partial_success',
            'message' => 'Campagne créée, mais les mails n\'ont pas pu partir. Vérifiez la connexion SMTP.'
        ], 201);
    }
}

return response()->json([
    'status'  => 'success',
    'message' => 'Campagne enregistrée ! (Les mails seront envoyés en arrière-plan)',
    'data'    => $campagne
], 201);
}

    /**
     * Récupérer toutes les campagnes (pour ta page de liste)
     */

    public function index()
{
    // On récupère les campagnes en comptant automatiquement le nombre de dons liés
    // Assure-toi d'avoir défini la relation public function dons() dans ton modèle Campagne
    $campagnes = Campagne::withCount('dons')
        ->orderBy('date_debut', 'desc')
        ->get();

    return response()->json($campagnes);
}

// Dans CampagneController.php

// 1. Pour la mise à jour (Bouton Details/Modifier)
public function update(Request $request, $id)
{
    $campagne = Campagne::findOrFail($id);

    $campagne->update([
        'titre' => $request->titre,
        'lieu' => $request->lieu,
        'capacite_max' => $request->capacite_max,
        'date_fin' => $request->date_fin,
        // Ajoute les autres champs si nécessaire
    ]);

    return response()->json($campagne);
}

// 2. Pour la suppression (Bouton Supprimer)
public function destroy($id)
{
    $campagne = Campagne::findOrFail($id);
    $campagne->delete();

    return response()->json(['message' => 'Supprimé avec succès']);
}
}
