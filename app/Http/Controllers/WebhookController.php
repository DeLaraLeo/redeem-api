<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\WebhookResult;
use App\Services\WebhookReceiverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookReceiverService $webhookReceiverService,
    ) {     
    }

    public function handle(Request $request): JsonResponse
    {
        $result = $this->webhookReceiverService->process(
            $request->getContent(),
            $request->header('X-GiftFlow-Signature')
        );

        return $this->toResponse($result);
    }

    private function toResponse(WebhookResult $result): JsonResponse
    {
        $data = $result->success
            ? ['status' => 'ok', 'message' => $result->message]
            : ['error' => $result->error, 'message' => $result->message];

        return response()->json($data, $result->status);
    }
}
