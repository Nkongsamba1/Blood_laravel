<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Donneur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. Validation des données arrivant de Vue.js
        $request->validate([
            'nom_complet' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'groupe_sanguin' => 'required|string',
            'telephone' => 'required|string',
        ]);

        // 2. Utilisation d'une transaction pour être sûr que tout est créé ou rien du tout
        return DB::transaction(function () use ($request) {

            // Création de l'utilisateur
            $user = User::create([
                'nom_complet' => $request->nom_complet,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_utilisateur' => 'donneur',
            ]);

            // Création du profil donneur lié
            Donneur::create([
                'utilisateur_id' => $user->utilisateur_id,
                'groupe_sanguin' => $request->groupe_sanguin,
                'genre' => $request->genre,
                'date_naissance' => $request->date_naissance,
                'telephone' => $request->telephone,
            ]);

            return response()->json(['message' => 'Inscription réussie'], 201);
        });
    }


    public function login(Request $request)
{
    // 1. Validation des champs
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    // 2. Vérification de l'utilisateur
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Identifiants incorrects'
        ], 401);
    }

    // 3. Création du Token Sanctum
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => $user
    ]);
}

public function logout(Request $request)
{
    // On supprime le token que l'utilisateur a utilisé pour cette session
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Déconnexion réussie']);
}
}
