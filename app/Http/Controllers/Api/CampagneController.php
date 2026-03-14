<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campagne;
use App\Models\User;
use App\Notifications\NouvelleCampagneNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class CampagneController extends Controller
{
    /**
     * Enregistrer une nouvelle campagne
     * L'envoi des notifications se fait en arrière-plan
     */
    public function store(Request $request)
    {
        // 1. Décodage des données JSON si elles arrivent sous forme de string (fréquent avec FormData)
        if (is_string($request->planning)) {
            $request->merge(['planning' => json_decode($request->planning, true)]);
        }
        if (is_string($request->groupes_cibles)) {
            $request->merge(['groupes_cibles' => json_decode($request->groupes_cibles, true)]);
        }

        // 2. Validation des données
        $validator = Validator::make($request->all(), [
            'titre'          => 'required|string|max:255',
            'lieu'           => 'required|string|max:255',
            'date_debut'     => 'required|date',
            'date_fin'       => 'required|date|after_or_equal:date_debut',
            'objectif'       => 'required|integer|min:1',
            'planning'       => 'required|array',
            'message'        => 'nullable|string',
            'groupes_cibles' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 3. Création de la campagne en base de données
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

        // 4. Ciblage des utilisateurs (Donneurs)
        $query = User::query();

        if (!empty($request->groupes_cibles)) {
            $query->whereHas('donneur', function ($q) use ($request) {
                $q->whereIn('groupe_sanguin', $request->groupes_cibles);
            });
        } else {
            $query->has('donneur');
        }

        $users = $query->get();

        // 5. Envoi de la notification en arrière-plan
        // Grâce à 'implements ShouldQueue' dans la classe NouvelleCampagneNotification,
        // cette ligne ne bloque pas le serveur et répond immédiatement au front-end.
        if ($users->count() > 0) {
            try {
                Notification::send($users, new NouvelleCampagneNotification($campagne));
            } catch (\Exception $e) {
                // On log l'erreur si la mise en file d'attente échoue
                Log::error("Échec de la mise en file d'attente des notifications : " . $e->getMessage());
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Campagne enregistrée ! Les notifications sont en cours d\'envoi en arrière-plan.',
            'data'    => $campagne
        ], 201);
    }

    /**
     * Liste des campagnes pour l'administration
     */
    public function index()
    {
        $campagnes = Campagne::withCount('dons')
            ->orderBy('date_debut', 'desc')
            ->get();

        return response()->json($campagnes);
    }

    /**
     * Mise à jour d'une campagne existante
     */
    public function update(Request $request, $id)
    {
        $campagne = Campagne::findOrFail($id);

        if (is_string($request->planning)) {
            $request->merge(['planning' => json_decode($request->planning, true)]);
        }

        $validator = Validator::make($request->all(), [
            'titre'        => 'required|string',
            'lieu'         => 'required|string',
            'capacite_max' => 'required|integer',
            'planning'     => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $campagne->update([
            'titre'        => $request->titre,
            'lieu'         => $request->lieu,
            'capacite_max' => $request->capacite_max,
            'date_fin'     => $request->date_fin,
            'planning'     => $request->planning,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Campagne mise à jour avec succès',
            'data' => $campagne
        ]);
    }

    /**
     * Supprimer une campagne
     */
    public function destroy($id)
    {
        $campagne = Campagne::findOrFail($id);
        $campagne->delete();

        return response()->json(['message' => 'Campagne supprimée avec succès']);
    }

    /**
     * API pour le calendrier des donneurs
     */
    public function disponibles()
    {
        $campagnes = Campagne::where('date_fin', '>=', now()->toDateString())
            ->orderBy('date_debut', 'asc')
            ->get();

        return response()->json($campagnes);
    }
}
