<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantIndexRequest;
use App\Http\Requests\TenantProvisionRequest;
use App\Http\Requests\TenantUserProvisionRequest;
use App\Services\Tenant\TenantAdministrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(private readonly TenantAdministrationService $tenants)
    {
    }

    public function index(TenantIndexRequest $request): JsonResponse
    {
        return response()->json(
            $this->tenants->listTenants(
                (int) $request->validated('page', 1),
                (int) $request->validated('perPage', 20),
            )
        );
    }

    public function store(TenantProvisionRequest $request): JsonResponse
    {
        $actor = $request->attributes->get('auth_user');

        return response()->json(
            $this->tenants->createTenant($actor, $request->validated()),
            201
        );
    }

    public function show(Request $request, string $tenantId): JsonResponse
    {
        $result = $this->tenants->showTenant($tenantId);

        if ($result === null) {
            return $this->errorResponse('NOT_FOUND', 'Tenant non trovato.', 404);
        }

        return response()->json($result);
    }

    public function storeUser(TenantUserProvisionRequest $request, string $tenantId): JsonResponse
    {
        $actor = $request->attributes->get('auth_user');
        $result = $this->tenants->addUser($actor, $tenantId, $request->validated());

        if ($result === null) {
            return $this->errorResponse('NOT_FOUND', 'Tenant non trovato.', 404);
        }

        if (($result['status'] ?? null) === 'tenant_inactive') {
            return $this->errorResponse('TENANT_INACTIVE', 'Il tenant selezionato non è attivo.', 409);
        }

        if (($result['status'] ?? null) === 'duplicate_user') {
            return $this->errorResponse('DUPLICATE_USER', 'Esiste già un utente con questa email.', 409, [
                'fieldErrors' => ['email' => ['Esiste già un utente con questa email.']],
            ]);
        }

        return response()->json($result, 201);
    }
}
