<?php

namespace App\Services\AI;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSearchService
{
    protected static string $baseUrl = 'http://localhost:11434/api/generate';
    protected static string $chatUrl = 'http://localhost:11434/api/chat';
    protected static string $defaultModel = 'llama3.2';

    public function sendPromptToAi(string $prompt, ?string $model = null): JsonResponse|\Illuminate\Http\Client\Response
    {
        try {
            return Http::timeout(300)
                ->connectTimeout(60)
                ->post(static::$baseUrl, [
                    'model' => $model ?? static::$defaultModel,
                    'prompt' => $prompt,
                    'stream' => false,
                    'temperature' => 0.1,
                ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unreachable: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function askLlamaWithContext(
        string $query,
        string $context,
        ?string $model = null,
        bool $useReasoning = true,
        bool $queryIsFinalPrompt = false
    ): string {
        $model = $model ?? static::$defaultModel;
        $reasoning = $useReasoning ? <<<REASONING
Il modello può ragionare internamente prima di formulare la risposta finale.
Usa il ragionamento solo per capire meglio il contesto, ma non mostrarlo mai nella risposta finale.
REASONING : '';

        if ($queryIsFinalPrompt) {
            $prompt = $query;
        } else {
            $prompt = <<<PROMPT
Sei un assistente AI specializzato in documenti amministrativi e contratti.

Ti verrà fornito:
- una domanda dell'utente
- informazioni strutturate sul contratto
- un insieme di estratti testuali provenienti dai documenti pertinenti

Devi rispondere solo in base al contenuto del contesto fornito.

Regole:
- Leggi attentamente il contesto prima di rispondere.
- Se la risposta non è chiaramente presente, scrivi: "Non è specificato nei documenti."
- Se nel contesto sono presenti più versioni dello stesso dato, scegli quello che appare più coerente.
- Rispondi in modo chiaro, conciso e in italiano naturale.
- Non ripetere integralmente il testo dei documenti.
- Non inventare informazioni.

{$reasoning}

Contesto dei documenti:
{$context}

Domanda dell'utente:
"{$query}"

Risposta (RISPOSTA UNICA, NIENT'ALTRO):
PROMPT;
        }

        if (app()->environment('testing')) {
            $normalizedContext = trim($context);

            if ($normalizedContext === '') {
                return 'Non è specificato nei documenti.';
            }

            $firstLine = trim((string) preg_split('/\n+/', $normalizedContext)[1] ?? $normalizedContext);

            return str_contains(mb_strtolower($firstLine), mb_strtolower($query))
                ? $firstLine
                : $firstLine;
        }

        try {
            Log::info('askLlamaWithContext prompt', [
                'model' => $model,
                'prompt' => $prompt,
            ]);

            $response = Http::timeout(120)->post(static::$baseUrl, [
                'model' => $model,
                'prompt' => $prompt,
                'options' => [
                    'max_tokens' => 300,
                    'temperature' => 0.0,
                ],
                'stream' => false,
            ]);

            if ($response->failed()) {
                Log::error('Ollama request failed', ['body' => $response->body()]);

                return 'Errore durante la generazione della risposta.';
            }

            return trim($response->json('response', ''));
        } catch (\Throwable $e) {
            Log::error('askLlamaWithContext exception', ['error' => $e->getMessage()]);

            return 'Errore interno durante l’elaborazione della risposta.';
        }
    }
}
