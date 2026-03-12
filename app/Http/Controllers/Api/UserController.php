<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
class UserController extends Controller
{

    // Récupérer le profil complet de l'admin connecté
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    // Gestion de l'upload de la photo
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        // Supprimer l'ancienne photo si elle existe pour ne pas encombrer le serveur
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        // Stocker la nouvelle photo dans le dossier 'profiles' du disque public
        $path = $request->file('photo')->store('profiles', 'public');

        // Mettre à jour la base de données
        $user->update(['photo' => $path]);

        return response()->json([
            'message' => 'Photo mise à jour avec succès',
            'photo_url' => $path
        ]);
    }
 public function index()
{
    // On récupère tout le monde, classé par rôle
    $users = User::orderBy('nom_complet')->get();

    return response()->json([
        'personnel' => $users->whereIn('role_utilisateur', ['admin', 'personnel'])->values(),
        'donneurs' => $users->where('role_utilisateur', 'donneur')->values()
    ]);
}

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);
    // On met à jour le rôle ou d'autres infos
    $user->update($request->only(['nom_complet', 'role_utilisateur', 'email']));

    return response()->json(['message' => 'Utilisateur mis à jour avec succès']);
}

    /**
     * Créer un nouveau membre du personnel
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom_complet' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_utilisateur' => 'required|in:admin,personnel',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'nom_complet' => $request->nom_complet,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Toujours hacher le mot de passe !
            'role_utilisateur' => $request->role_utilisateur,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user
        ], 201);
    }


    /**
     * Supprimer (ou bannir) un utilisateur
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Sécurité : on ne peut pas supprimer le dernier admin par erreur
        if ($user->role_utilisateur === 'admin' && User::where('role_utilisateur', 'admin')->count() <= 1) {
            return response()->json(['message' => 'Impossible de supprimer le dernier administrateur'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    // Dans UserController.php ou AuthController.php

}
