<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MfaVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge_id' => ['required', 'string'],
            'verification_code' => ['required', 'string', 'min:6'],
        ];
    }
}
