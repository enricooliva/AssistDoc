<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function errorResponse(string $code, string $message, int $status, array $extra = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                ...$extra,
            ],
        ], $status);
    }

    protected function validationErrorResponse(array $details): array
    {
        return [
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'message' => 'La richiesta non e valida.',
                'fieldErrors' => $details,
            ],
        ];
    }
}
