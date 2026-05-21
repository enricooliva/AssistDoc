<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnterpriseUserIndexRequest;
use App\Http\Requests\EnterpriseUserStatusUpdateRequest;
use App\Http\Requests\EnterpriseUserStoreRequest;
use App\Services\Auth\EnterpriseUserLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly EnterpriseUserLifecycleService $lifecycleService)
    {
    }

    public function index(EnterpriseUserIndexRequest $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json($this->lifecycleService->listUsers(
            $user['tenant_id'],
            (int) $request->validated('page', 1),
            (int) $request->validated('perPage', 20),
        ));
    }

    public function store(EnterpriseUserStoreRequest $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json(
            $this->lifecycleService->createUser($user['tenant_id'], $user['id'], $request->validated()),
            201
        );
    }

    public function show(Request $request, string $userId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $result = $this->lifecycleService->showUser($user['tenant_id'], $userId);

        if ($result === null) {
            return $this->errorResponse('NOT_FOUND', 'Utente non trovato.', 404);
        }

        return response()->json($result);
    }

    public function updateStatus(EnterpriseUserStatusUpdateRequest $request, string $userId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $result = $this->lifecycleService->updateStatus(
            $user['tenant_id'],
            $user['id'],
            $userId,
            $request->validated('status'),
            $request->validated('reason')
        );

        if ($result === null) {
            return $this->errorResponse('NOT_FOUND', 'Utente non trovato.', 404);
        }

        if (($result['status'] ?? null) === 'invalid_transition') {
            return $this->errorResponse('INVALID_LIFECYCLE_STATE', 'Transizione di stato non consentita.', 409);
        }

        return response()->json($result);
    }

    public function unlock(Request $request, string $userId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $result = $this->lifecycleService->unlockUser($user['tenant_id'], $user['id'], $userId);

        if ($result === null) {
            return $this->errorResponse('NOT_FOUND', 'Utente non trovato.', 404);
        }

        return response()->json($result);
    }
}
