<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateChatConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $title = $this->input('title');

        if (is_string($title)) {
            $trimmed = trim($title);

            $this->merge([
                'title' => $trimmed === '' ? null : $trimmed,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'Il titolo della conversazione non può superare 120 caratteri.',
        ];
    }
}
