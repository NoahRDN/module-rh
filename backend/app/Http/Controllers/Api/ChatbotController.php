<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatbotController extends Controller
{
    private ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Envoyer une question au chatbot
     */
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|max:1000',
            'context' => 'nullable',
        ]);

        $user = $request->user();
        $question = $request->input('question');
        $context = $request->input('context', null);

        try {
            $response = $this->chatbotService->processQuestion($question, $user, $context);

            if (!$response['success']) {
                return response()->json([
                    'success' => false,
                    'reponse' => $response['response'],
                    'erreur' => $response['error'] ?? 'Erreur inconnue',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'reponse' => $response['response'],
                'intent' => $response['intent'] ?? null,
                'data' => $response['data'] ?? null,
                'suggestions' => $response['suggestions'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Chatbot error in controller: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'reponse' => 'Désolé, une erreur s\'est produite. Veuillez réessayer ou contacter le support.',
                'erreur' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Obtenir des suggestions de questions
     */
    public function suggestions(Request $request): JsonResponse
    {
        $user = $request->user();
        $suggestions = $this->chatbotService->getSuggestions($user);

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Obtenir l'historique des conversations (si implémenté)
     */
    public function historique(Request $request): JsonResponse
    {
        // Pour une future implémentation avec stockage des conversations
        return response()->json([
            'historique' => [],
            'message' => 'Historique non disponible pour le moment',
        ]);
    }
}
