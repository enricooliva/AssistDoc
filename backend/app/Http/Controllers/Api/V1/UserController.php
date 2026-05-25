<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteEnterpriseUserRequest;
use App\Http\Requests\EnterpriseUserIndexRequest;
use App\Http\Requests\EnterpriseUserUpdateRequest;
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
            $request->validated('query'),
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

    public function update(EnterpriseUserUpdateRequest $request, string $userId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $result = $this->lifecycleService->updateUser($user, $userId, $request->validated());

        if ($result === null) {
            return $this->errorResponse('NOT_FOUND', 'Utente non trovato.', 404);
        }

        if (($result['status'] ?? null) === 'update_denied') {
            return $this->errorResponse(
                $result['error']['code'] ?? 'ACCESS_DENIED',
                $result['error']['message'] ?? 'L\'account non è autorizzato a completare questa operazione.',
                403
            );
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

    public function destroy(DeleteEnterpriseUserRequest $request, string $userId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $result = $this->lifecycleService->deleteUser($user, $userId);

        if ($result === null) {
            return $this->errorResponse('NOT_FOUND', 'Utente non trovato.', 404);
        }

        if (($result['status'] ?? null) === 'already_deleted') {
            return $this->errorResponse('ALREADY_DELETED', 'L\'utente è già stato eliminato.', 409);
        }

        if (($result['status'] ?? null) === 'delete_denied') {
            return $this->errorResponse('DELETE_NOT_ALLOWED', 'L\'utente selezionato non può essere eliminato.', 409);
        }

        return response()->json($result);
    }
}
