<?php

use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/auth/options', [AuthController::class, 'options']);
    Route::post('/auth/company-account/start', [AuthController::class, 'companyAccountStart']);
    Route::post('/auth/password/login', [AuthController::class, 'passwordLogin']);
    Route::post('/auth/mfa/verify', [AuthController::class, 'verifyMfa']);
    Route::post('/auth/password-reset', [AuthController::class, 'startPasswordReset']);
    Route::post('/auth/password-reset/complete', [AuthController::class, 'completePasswordReset']);

    Route::middleware('auth.token')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/documents', [DocumentController::class, 'index'])->middleware('role:super-admin,operator,viewer');
        Route::post('/documents', [DocumentController::class, 'store'])->middleware('role:super-admin,operator');
        Route::get('/documents/{documentId}', [DocumentController::class, 'show'])->middleware('role:super-admin,operator,viewer');
        Route::delete('/documents/{documentId}', [DocumentController::class, 'destroy'])->middleware('role:super-admin,operator');
        Route::post('/documents/{documentId}/retry', [DocumentController::class, 'retry'])->middleware('role:super-admin,operator');
        Route::post('/documents/{documentId}/preparation-runs', [DocumentController::class, 'startPreparationRun'])->middleware('role:super-admin,operator');
        Route::get('/documents/{documentId}/preparation-runs/{runId}', [DocumentController::class, 'showPreparationRun'])->middleware('role:super-admin,operator,viewer');

        Route::get('/rag/chunking-profiles', [DocumentController::class, 'listChunkingProfiles'])->middleware('role:super-admin,operator');

        Route::post('/search/queries', [SearchController::class, 'query'])->middleware('role:super-admin,operator,viewer');
        Route::get('/chat/conversations', [ChatController::class, 'index'])->middleware('role:super-admin,operator,viewer');
        Route::post('/chat/conversations', [ChatController::class, 'store'])->middleware('role:super-admin,operator,viewer');
        Route::get('/chat/conversations/{conversationId}', [ChatController::class, 'show'])->middleware('role:super-admin,operator,viewer');
        Route::post('/chat/conversations/{conversationId}/archive', [ChatController::class, 'archive'])->middleware('role:super-admin,operator,viewer');
        Route::delete('/chat/conversations/{conversationId}', [ChatController::class, 'destroy'])->middleware('role:super-admin,operator,viewer');
        Route::post('/chat/conversations/{conversationId}/messages', [ChatController::class, 'message'])->middleware('role:super-admin,operator,viewer');

        Route::get('/audit-events', [AuditController::class, 'index'])->middleware('role:super-admin');
        Route::get('/users', [UserController::class, 'index'])->middleware('role:super-admin');
        Route::post('/users', [UserController::class, 'store'])->middleware('role:super-admin');
        Route::get('/users/{userId}', [UserController::class, 'show'])->middleware('role:super-admin');
        Route::delete('/users/{userId}', [UserController::class, 'destroy'])->middleware('role:super-admin');
        Route::patch('/users/{userId}/status', [UserController::class, 'updateStatus'])->middleware('role:super-admin');
        Route::post('/users/{userId}/unlock', [UserController::class, 'unlock'])->middleware('role:super-admin');
    });
});
