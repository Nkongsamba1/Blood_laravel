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
     */
    public function store(Request $request)
    {
        // On décode le planning s'il arrive sous forme de chaîne JSON (venant du front)
        if (is_string($request->planning)) {
            $request->merge(['planning' => json_decode($request->planning, true)]);
        }
        if (is_string($request->groupes_cibles)) {
            $request->merge(['groupes_cibles' => json_decode($request->groupes_cibles, true)]);
        }

        // 1. Validation des données
        $validator = Validator::make($request->all(), [
            'titre'          => 'required|string|max:255',
            'lieu'           => 'required|string|max:255',
            'date_debut'     => 'required|date',
            'date_fin'       => 'required|date|after_or_equal:date_debut',
            'objectif'       => 'required|integer|min:1',
            'planning'       => 'required|array', // Maintenant c'est bien un array
            'message'        => 'nullable|string',
            'groupes_cibles' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Création de la campagne
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

        // 3. Ciblage des utilisateurs
        $query = User::query();
        if (!empty($request->groupes_cibles)) {
            $query->whereHas('donneur', function ($q) use ($request) {
                $q->whereIn('groupe_sanguin', $request->groupes_cibles);
            });
        } else {
            $query->has('donneur');
        }

        $users = $query->get();

        // 4. Envoi de la notification
        if ($users->count() > 0) {
            try {
                Notification::send($users, new NouvelleCampagneNotification($campagne));
            } catch (\Exception $e) {
                Log::error("Erreur d'envoi notification : " . $e->getMessage());
                return response()->json([
                    'status' => 'partial_success',
                    'message' => 'Campagne créée, mais erreur SMTP.',
                    'data' => $campagne
                ], 201);
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Campagne enregistrée et notifications envoyées !',
            'data'    => $campagne
        ], 201);
    }

    /**
     * Liste des campagnes
     */
    public function index()
    {
        $campagnes = Campagne::withCount('dons')
            ->orderBy('date_debut', 'desc')
            ->get();

        return response()->json($campagnes);
    }

    /**
     * Mise à jour d'une campagne
     */
    public function update(Request $request, $id)
    {
        $campagne = Campagne::findOrFail($id);

        // Décodage si nécessaire pour la validation
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
            'message' => 'Campagne mise à jour',
            'data' => $campagne
        ]);
    }

    /**
     * Suppression
     */
    public function destroy($id)
    {
        $campagne = Campagne::findOrFail($id);
        $campagne->delete();

        return response()->json(['message' => 'Supprimé avec succès']);
    }

    /**
     * Campagnes disponibles pour les donneurs (Front-end mobile/web)
     */
    public function disponibles()
    {
        $campagnes = Campagne::where('date_fin', '>=', now()->toDateString())
            ->orderBy('date_debut', 'asc')
            ->get();

        return response()->json($campagnes);
    }
}
