<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatMessageRequest;
use App\Http\Requests\CreateChatConversationRequest;
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

    public function store(CreateChatConversationRequest $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        return response()->json(
            $this->chatService->createConversation($user['tenant_id'], $user['id'], $request->input('title')),
            201
        );
    }

    public function show(Request $request, string $conversationId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $conversation = $this->chatService->showConversation($user['tenant_id'], $user['id'], $conversationId);

        if ($conversation === null) {
            return $this->errorResponse('NOT_FOUND', 'Conversazione non trovata.', 404);
        }

        return response()->json($conversation);
    }

    public function archive(Request $request, string $conversationId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $conversation = $this->chatService->archiveConversation($user['tenant_id'], $user['id'], $conversationId);

        if ($conversation === null) {
            return $this->errorResponse('NOT_FOUND', 'Conversazione non trovata.', 404);
        }

        return response()->json($conversation);
    }

    public function message(ChatMessageRequest $request, string $conversationId): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        $exchange = $this->chatService->answerQuestion(
            $user['tenant_id'],
            $user['id'],
            $conversationId,
            $request->validated('question')
        );

        if ($exchange === null) {
            return $this->errorResponse('NOT_FOUND', 'Conversazione non trovata.', 404);
        }

        if (isset($exchange['error'])) {
            return $this->errorResponse(
                $exchange['error']['code'],
                $exchange['error']['message'],
                409
            );
        }

        return response()->json($exchange);
    }
}
