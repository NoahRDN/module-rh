<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Connexion
    public function login(Request $request)
    {
        $request->validate([
            'identifiant' => 'required|string',
            'mdp' => 'required|string'
        ]);

        $user = Utilisateur::where('identifiant', $request->identifiant)->first();

        if (!$user || !Hash::check($request->mdp, $user->mdp)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id_utilisateur,
                'identifiant' => $user->identifiant,
                'role' => $user->id_role
            ]
        ]);
    }

    // Déconnexion
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    // Infos utilisateur connecté
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
