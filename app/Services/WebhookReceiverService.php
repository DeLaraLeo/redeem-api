<?php

namespace App\Services;

use App\DataTransferObjects\WebhookResult;
use App\Repositories\ReceivedWebhookRepositoryInterface;
use Illuminate\Support\Facades\Log;

class WebhookReceiverService
{
    public function __construct(
        private WebhookSignatureService $signatureService,
        private ReceivedWebhookRepositoryInterface $receivedWebhookRepository,
    ) {     
    }

    public function process(string $rawBody, ?string $signature): WebhookResult
    {
        if (!$this->isValidSignature($rawBody, $signature)) {
            return WebhookResult::failure(
                error: 'invalid_signature',
                message: 'Webhook signature validation failed.',
                status: 401
            );
        }

        $payload = json_decode($rawBody, true) ?? [];
        $eventId = $payload['event_id'] ?? null;

        if (!$eventId) {
            return WebhookResult::failure(
                error: 'missing_event_id',
                message: 'Event ID is required.',
                status: 400
            );
        }

        if ($this->receivedWebhookRepository->hasReceived($eventId)) {
            $this->logDuplicate($eventId);
            
            return WebhookResult::success('Webhook already processed.');
        }

        $this->receivedWebhookRepository->markAsReceived($eventId);
        $this->logProcessed($eventId, $payload['type'] ?? 'unknown');

        return WebhookResult::success('Webhook received successfully.');
    }

    private function isValidSignature(string $rawBody, ?string $signature): bool
    {
        if (!$signature) {
            Log::warning('Webhook missing signature');
            return false;
        }

        if (!$this->signatureService->verify($rawBody, $signature)) {
            Log::warning('Webhook invalid signature', [
                'signature_prefix' => substr($signature, 0, 16) . '...',
            ]);
            return false;
        }

        return true;
    }

    private function logDuplicate(string $eventId): void
    {
        Log::info('Webhook duplicate', [
            'event_id' => $eventId,
            'action' => 'skipped',
        ]);
    }

    private function logProcessed(string $eventId, string $type): void
    {
        Log::info('Webhook processed', [
            'event_id' => $eventId,
            'type' => $type,
            'action' => 'processed',
        ]);
    }
}
