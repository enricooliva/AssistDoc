<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRetryRequest;
use App\Http\Requests\DocumentUploadRequest;
use App\Services\Documents\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json($this->documentService->list($user['tenant_id']));
    }

    public function store(DocumentUploadRequest $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json(
            $this->documentService->create($user['tenant_id'], $user['id'], $request->validated()),
            201
        );
    }

    public function show(Request $request, string $documentId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $document = $this->documentService->show($user['tenant_id'], $documentId);

        if (! $document) {
            return response()->json([
                'code' => 'not_found',
                'message' => 'Documento non trovato.',
            ], 404);
        }

        return response()->json($document);
    }

    public function retry(DocumentRetryRequest $request, string $documentId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json(
            $this->documentService->retry($user['tenant_id'], $user['id'], $documentId),
            202
        );
    }
}

