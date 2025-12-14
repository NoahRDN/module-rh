<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Liste des notifications de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = $user->notifications();

            if ($request->has('lu')) {
                $query->where('lu', $request->boolean('lu'));
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->take($request->get('limit', 50))
                ->get();

            return response()->json($notifications);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération notifications', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Nombre de notifications non lues
     */
    public function countNonLues(Request $request)
    {
        try {
            $count = $request->user()->notificationsNonLues()->count();
            return response()->json(['count' => $count]);
        } catch (\Throwable $e) {
            Log::error('Erreur comptage notifications', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function marquerLue($id)
    {
        try {
            $notification = Notification::findOrFail($id);
            $notification->marquerCommeLu();

            return response()->json(['message' => 'Notification marquée comme lue']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Notification non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur marquage notification', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function marquerToutesLues(Request $request)
    {
        try {
            $request->user()->notificationsNonLues()->update([
                'lu' => true,
                'lu_at' => now(),
            ]);

            return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues']);
        } catch (\Throwable $e) {
            Log::error('Erreur marquage notifications', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Supprimer une notification
     */
    public function destroy($id)
    {
        try {
            $notification = Notification::findOrFail($id);
            $notification->delete();

            return response()->json(['message' => 'Notification supprimée']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Notification non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression notification', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Supprimer toutes les notifications lues
     */
    public function supprimerLues(Request $request)
    {
        try {
            $request->user()->notifications()->where('lu', true)->delete();

            return response()->json(['message' => 'Notifications lues supprimées']);
        } catch (\Throwable $e) {
            Log::error('Erreur suppression notifications', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
