<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchQueryRequest;
use App\Services\Search\SemanticSearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(private readonly SemanticSearchService $searchService)
    {
    }

    public function query(SearchQueryRequest $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json(
            $this->searchService->query($user['tenant_id'], $user['id'], $request->validated('query'))
        );
    }
}

