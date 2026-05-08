<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatMessageRequest;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json($this->chatService->listConversations($user['tenant_id'], $user['id']));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json(
            $this->chatService->createConversation($user['tenant_id'], $user['id'], $request->input('title')),
            201
        );
    }

    public function message(ChatMessageRequest $request, string $conversationId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json(
            $this->chatService->answerQuestion($user['tenant_id'], $user['id'], $conversationId, $request->validated('question'))
        );
    }
}
