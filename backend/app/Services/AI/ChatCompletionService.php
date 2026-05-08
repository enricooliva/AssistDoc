<?php

namespace App\Services\AI;

class ChatCompletionService
{
    public function answer(string $question, array $results): array
    {
        if ($results === []) {
            return [
                'body' => 'Non ho trovato informazioni sufficienti nei documenti del tenant per rispondere in modo affidabile.',
                'responseState' => 'insufficient_information',
            ];
        }

        return [
            'body' => 'In base ai documenti del tenant, AssistDoc applica isolamento lato server su tutte le risorse e fornisce risposte con citazioni verificabili.',
            'responseState' => 'completed',
        ];
    }
}

