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

        if (is_string($question)) {
            $this->merge([
                'question' => trim($question),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:1', 'max:4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'Inserisci una domanda prima di inviare il messaggio.',
            'question.min' => 'Inserisci una domanda prima di inviare il messaggio.',
            'question.max' => 'La domanda supera la lunghezza massima consentita.',
        ];
    }
}
