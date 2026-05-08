<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\AuditEventFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuditEventIndexRequest;
use App\Services\Audit\AuditQueryService;
use Illuminate\Http\JsonResponse;

class AuditController extends Controller
{
    public function __construct(private readonly AuditQueryService $auditQueryService)
    {
    }

    public function index(AuditEventIndexRequest $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json(
            $this->auditQueryService->index($user['tenant_id'], AuditEventFilters::fromArray($request->validated()))
        );
    }
}
