<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $question = $this->input('question');
        $tags = $this->input('tags');

        if (is_string($question)) {
            $this->merge([
                'question' => trim($question),
            ]);
        }

        if (is_array($tags)) {
            $this->merge([
                'tags' => array_values(array_filter(array_map(
                    static fn ($tag): string => trim((string) $tag),
                    $tags
                ), static fn (string $tag): bool => $tag !== '')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:1', 'max:4000'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
            'chunkingProfileId' => ['nullable', 'string', 'exists:chunking_profiles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'Inserisci una domanda prima di inviare il messaggio.',
            'question.min' => 'Inserisci una domanda prima di inviare il messaggio.',
            'question.max' => 'La domanda supera la lunghezza massima consentita.',
            'tags.*.max' => 'Ogni tag deve contenere al massimo 50 caratteri.',
        ];
    }
}
