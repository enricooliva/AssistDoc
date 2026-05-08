<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function validationErrorResponse(array $details): array
    {
        return [
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'message' => 'La richiesta non e valida.',
                'details' => $details,
            ],
        ];
    }
}
