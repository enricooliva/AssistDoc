<?php

namespace App\Http\Requests;

class EnterpriseUserIndexRequest extends DocumentIndexRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'query' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }
}
