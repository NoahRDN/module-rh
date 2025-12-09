<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // Connexion
    public function login(Request $request)
    {
        Log::info('Tentative de login');
        try {
            $request->validate([
                'identifiant' => 'required|string',
                'mdp' => 'required|string'
            ]);

            $login = $request->identifiant;
            // Auth sur la table users (email comme login)
            Log::debug('Tentative de login pour ', ['identifiant' => $login]);
            $user = User::where('email', $login)->first();
            if (!$user || $user->password !== $request->mdp) {
                return response()->json(['message' => 'Identifiants invalides'], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'identifiant' => $user->email,
                    'role' => $user->role
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur login', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    // Déconnexion
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Déconnexion réussie']);
        } catch (\Throwable $e) {
            Log::error('Erreur logout', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    // Infos utilisateur connecté
    public function me(Request $request)
    {
        try {
            return response()->json($request->user());
        } catch (\Throwable $e) {
            Log::error('Erreur me', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
