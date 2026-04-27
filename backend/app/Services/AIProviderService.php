<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIProviderService
{
    public function chatText(string $systemPrompt, string $userPrompt, array $options = []): string
    {
        $provider = (string) config('services.ai_provider', 'openai');

        return match ($provider) {
            'openai' => $this->openAIText($systemPrompt, $userPrompt, $options),
            'gemini' => $this->geminiText($systemPrompt, $userPrompt, $options),
            default => throw new \Exception("Unsupported AI provider: $provider"),
        };
    }

    public function chatJson(string $systemPrompt, string $userPrompt, array $options = [])
    {
        $provider = (string) config('services.ai_provider', 'openai');

        return match ($provider) {
            'openai' => $this->openAI($systemPrompt, $userPrompt, $options),
            'gemini' => $this->gemini($userPrompt),
            default => throw new \Exception("Unsupported AI provider: $provider"),
        };
    }

    private function openAIText(string $systemPrompt, string $userPrompt, array $options): string
    {
        $apiKey = (string) (config('services.openai.api_key') ?? '');
        $apiUrl = (string) (config('services.openai.api_url') ?? '');
        $model = (string) (config('services.openai.model') ?? 'gpt-4o-mini');

        if ($apiKey === '' || $apiUrl === '') {
            throw new \Exception('OpenAI non configuré (OPENAI_API_KEY/OPENAI_API_URL manquant)');
        }

        $response = Http::withToken($apiKey)
            ->post($apiUrl, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 1000,
            ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI error: ' . $response->body());
        }

        $content = (string) $response->json('choices.0.message.content');
        return $content !== '' ? $content : '';
    }

    private function openAI(string $systemPrompt, string $userPrompt, array $options)
    {
        $apiKey = (string) (config('services.openai.api_key') ?? '');
        $apiUrl = (string) (config('services.openai.api_url') ?? '');
        $model = (string) (config('services.openai.model') ?? 'gpt-4o-mini');

        if ($apiKey === '' || $apiUrl === '') {
            throw new \Exception('OpenAI non configuré (OPENAI_API_KEY/OPENAI_API_URL manquant)');
        }

        $response = Http::withToken($apiKey)
            ->post($apiUrl, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 1000,
            ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI error: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');

        return json_decode($content, true) ?? $content;
    }

    private function gemini(string $userPrompt)
    {
        $apiUrl = (string) (config('services.gemini.api_url') ?? '');
        $apiKey = (string) (config('services.gemini.api_key') ?? '');

        if ($apiUrl === '' || $apiKey === '') {
            throw new \Exception('Gemini non configuré (GEMINI_API_URL/GEMINI_API_KEY manquant)');
        }

        $response = Http::post(
            $apiUrl . '?key=' . $apiKey,
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $userPrompt]
                        ]
                    ]
                ]
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Gemini error: ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text');
    }

    private function geminiText(string $systemPrompt, string $userPrompt, array $options): string
    {
        $apiUrl = (string) (config('services.gemini.api_url') ?? '');
        $apiKey = (string) (config('services.gemini.api_key') ?? '');

        if ($apiUrl === '' || $apiKey === '') {
            throw new \Exception('Gemini non configuré (GEMINI_API_URL/GEMINI_API_KEY manquant)');
        }

        // Gemini ne supporte pas toujours un rôle system via ce endpoint selon versions;
        // on inclut donc le prompt système dans le texte envoyé.
        $combinedPrompt = trim($systemPrompt . "\n\n" . $userPrompt);

        $response = Http::post(
            $apiUrl . '?key=' . $apiKey,
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $combinedPrompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? 0.7,
                    // Gemini utilise maxOutputTokens
                    'maxOutputTokens' => $options['max_tokens'] ?? 1000,
                ],
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Gemini error: ' . $response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        return is_string($text) ? $text : '';
    }
}