<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessagePieceJointe;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MessagerieController extends Controller
{
    /**
     * Liste des conversations (pour RH: toutes, pour employé: les siennes)
     */
    public function conversations(Request $request)
    {
        try {
            $user = $request->user();
            $query = Conversation::with(['employe', 'assigneA', 'dernierMessage']);

            // Si l'utilisateur est un employé, ne montrer que ses conversations
            if ($user->isEmploye() && $user->employe_id) {
                $query->where('employe_id', $user->employe_id);
            }

            // Filtres
            if ($request->has('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->has('assigne_a') && !$user->isEmploye()) {
                $query->where('assigne_a', $request->assigne_a);
            }

            $conversations = $query->orderBy('derniere_activite', 'desc')->get();

            // Ajouter le nombre de messages non lus
            $conversations->each(function($conv) use ($user) {
                $conv->messages_non_lus = $conv->messages()
                    ->where('lu', false)
                    ->where('user_id', '!=', $user->id)
                    ->count();
            });

            return response()->json($conversations);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération conversations', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Créer une nouvelle conversation
     */
    public function creerConversation(Request $request)
    {
        try {
            $validated = $request->validate([
                'sujet' => 'required|string|max:255',
                'message' => 'required|string',
                'priorite' => 'nullable|in:basse,normale,haute,urgente',
            ]);

            $user = $request->user();

            // Déterminer l'employe_id
            $employeId = $user->employe_id;
            if (!$employeId && $request->has('employe_id')) {
                $employeId = $request->employe_id;
            }

            if (!$employeId) {
                return response()->json(['message' => 'Employé non spécifié'], 422);
            }

            $conversation = Conversation::create([
                'sujet' => $validated['sujet'],
                'employe_id' => $employeId,
                'statut' => 'ouverte',
                'priorite' => $validated['priorite'] ?? 'normale',
                'derniere_activite' => now(),
            ]);

            // Créer le premier message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'contenu' => $validated['message'],
            ]);

            // Notifier le service RH
            $this->notifierNouvelleConversation($conversation);

            $conversation->load(['employe', 'messages.user']);

            return response()->json($conversation, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur création conversation', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Détail d'une conversation avec ses messages
     */
    public function showConversation($id)
    {
        try {
            $conversation = Conversation::with(['employe', 'assigneA', 'messages.user', 'messages.piecesJointes'])
                ->findOrFail($id);

            return response()->json($conversation);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Conversation non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur récupération conversation', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Envoyer un message dans une conversation
     */
    public function envoyerMessage(Request $request, $conversationId)
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);

            if ($conversation->statut === 'fermee') {
                return response()->json(['message' => 'Cette conversation est fermée'], 422);
            }

            $validated = $request->validate([
                'contenu' => 'required|string',
            ]);

            $user = $request->user();

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'contenu' => $validated['contenu'],
            ]);

            // Notifier le destinataire
            $this->notifierNouveauMessage($conversation, $message, $user);

            $message->load(['user', 'piecesJointes']);

            return response()->json($message, 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Conversation non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur envoi message', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Ajouter une pièce jointe à un message
     */
    public function ajouterPieceJointe(Request $request, $conversationId)
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);

            if ($conversation->statut === 'fermee') {
                return response()->json(['message' => 'Cette conversation est fermée'], 422);
            }

            $validated = $request->validate([
                'contenu' => 'nullable|string',
                'fichier' => 'required|file|max:10240', // Max 10MB
            ]);

            $user = $request->user();
            $file = $request->file('fichier');

            // Créer le message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'contenu' => $validated['contenu'] ?? '[Pièce jointe]',
            ]);

            // Sauvegarder le fichier
            $path = $file->store('messages/' . $conversation->id, 'public');

            // Créer la pièce jointe
            $pieceJointe = MessagePieceJointe::create([
                'message_id' => $message->id,
                'nom' => $file->getClientOriginalName(),
                'chemin' => $path,
                'type_mime' => $file->getMimeType(),
                'taille' => $file->getSize(),
            ]);

            // Notifier le destinataire
            $this->notifierNouveauMessage($conversation, $message, $user);

            $message->load(['user', 'piecesJointes']);

            return response()->json($message, 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Conversation non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur ajout pièce jointe', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Marquer les messages d'une conversation comme lus
     */
    public function marquerLu(Request $request, $conversationId)
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);
            $user = $request->user();

            $conversation->messages()
                ->where('user_id', '!=', $user->id)
                ->where('lu', false)
                ->update([
                    'lu' => true,
                    'lu_at' => now(),
                ]);

            return response()->json(['message' => 'Messages marqués comme lus']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Conversation non trouvée'], 404);
        } catch (\Throwable $e) {
            Log::error('Erreur marquage messages', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Assigner une conversation à un utilisateur RH
     */
    public function assigner(Request $request, $conversationId)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $conversation = Conversation::findOrFail($conversationId);
            $conversation->assigne_a = $validated['user_id'];
            $conversation->save();

            // Notifier l'utilisateur assigné
            $userAssigne = User::find($validated['user_id']);
            Notification::creer(
                $userAssigne->id,
                'conversation_assignee',
                'Conversation assignée',
                sprintf('La conversation "%s" vous a été assignée.', $conversation->sujet),
                ['conversation_id' => $conversation->id]
            );

            $conversation->load('assigneA');

            return response()->json($conversation);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Conversation non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur assignation conversation', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Changer le statut d'une conversation
     */
    public function changerStatut(Request $request, $conversationId)
    {
        try {
            $validated = $request->validate([
                'statut' => 'required|in:ouverte,en_attente,fermee',
            ]);

            $conversation = Conversation::findOrFail($conversationId);
            $conversation->statut = $validated['statut'];
            $conversation->save();

            // Notifier l'employé si la conversation est fermée
            if ($validated['statut'] === 'fermee') {
                $user = $conversation->employe->user;
                if ($user) {
                    Notification::creer(
                        $user->id,
                        'conversation_fermee',
                        'Conversation fermée',
                        sprintf('La conversation "%s" a été fermée.', $conversation->sujet),
                        ['conversation_id' => $conversation->id]
                    );
                }
            }

            return response()->json($conversation);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Conversation non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur changement statut', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Nombre de conversations/messages non lus
     */
    public function statsNonLus(Request $request)
    {
        try {
            $user = $request->user();
            
            $query = Conversation::query();
            if ($user->isEmploye() && $user->employe_id) {
                $query->where('employe_id', $user->employe_id);
            }

            $conversationsIds = $query->pluck('id');

            $messagesNonLus = Message::whereIn('conversation_id', $conversationsIds)
                ->where('user_id', '!=', $user->id)
                ->where('lu', false)
                ->count();

            $conversationsNonLues = Message::whereIn('conversation_id', $conversationsIds)
                ->where('user_id', '!=', $user->id)
                ->where('lu', false)
                ->distinct('conversation_id')
                ->count('conversation_id');

            return response()->json([
                'messages_non_lus' => $messagesNonLus,
                'conversations_non_lues' => $conversationsNonLues,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur stats non lus', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Notifier le service RH d'une nouvelle conversation
     */
    private function notifierNouvelleConversation(Conversation $conversation): void
    {
        $rhUsers = User::whereIn('role', ['rh', 'admin'])->get();

        foreach ($rhUsers as $user) {
            Notification::creer(
                $user->id,
                'nouvelle_conversation',
                'Nouveau message RH',
                sprintf(
                    '%s %s a ouvert une nouvelle conversation: "%s"',
                    $conversation->employe->prenom,
                    $conversation->employe->nom,
                    $conversation->sujet
                ),
                ['conversation_id' => $conversation->id]
            );
        }
    }

    /**
     * Notifier d'un nouveau message
     */
    private function notifierNouveauMessage(Conversation $conversation, Message $message, User $expediteur): void
    {
        // Si c'est un employé qui envoie, notifier le RH assigné ou tous les RH
        if ($expediteur->isEmploye()) {
            if ($conversation->assigne_a) {
                Notification::creer(
                    $conversation->assigne_a,
                    'nouveau_message',
                    'Nouveau message',
                    sprintf('Nouveau message dans la conversation "%s"', $conversation->sujet),
                    ['conversation_id' => $conversation->id, 'message_id' => $message->id]
                );
            } else {
                $rhUsers = User::whereIn('role', ['rh', 'admin'])->get();
                foreach ($rhUsers as $user) {
                    Notification::creer(
                        $user->id,
                        'nouveau_message',
                        'Nouveau message',
                        sprintf('Nouveau message dans la conversation "%s"', $conversation->sujet),
                        ['conversation_id' => $conversation->id, 'message_id' => $message->id]
                    );
                }
            }
        } else {
            // Si c'est un RH qui envoie, notifier l'employé
            $employeUser = $conversation->employe->user;
            if ($employeUser) {
                Notification::creer(
                    $employeUser->id,
                    'nouveau_message',
                    'Nouveau message du service RH',
                    sprintf('Vous avez reçu une réponse dans la conversation "%s"', $conversation->sujet),
                    ['conversation_id' => $conversation->id, 'message_id' => $message->id]
                );
            }
        }
    }
}
