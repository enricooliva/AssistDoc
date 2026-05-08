<?php

use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth.token')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/documents', [DocumentController::class, 'index'])->middleware('role:super-admin,operator,viewer');
        Route::post('/documents', [DocumentController::class, 'store'])->middleware('role:super-admin,operator');
        Route::get('/documents/{documentId}', [DocumentController::class, 'show'])->middleware('role:super-admin,operator,viewer');
        Route::post('/documents/{documentId}/retry', [DocumentController::class, 'retry'])->middleware('role:super-admin,operator');

        Route::post('/search/queries', [SearchController::class, 'query'])->middleware('role:super-admin,operator,viewer');
        Route::get('/chat/conversations', [ChatController::class, 'index'])->middleware('role:super-admin,operator,viewer');
        Route::post('/chat/conversations', [ChatController::class, 'store'])->middleware('role:super-admin,operator,viewer');
        Route::post('/chat/conversations/{conversationId}/messages', [ChatController::class, 'message'])->middleware('role:super-admin,operator,viewer');

        Route::get('/audit-events', [AuditController::class, 'index'])->middleware('role:super-admin');
    });
});
