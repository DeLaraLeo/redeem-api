<?php

namespace App\Jobs;

use App\Services\WebhookSignatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [5, 30, 120];

    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        public readonly string $eventId,
        public readonly string $code,
        public readonly string $email,
        public readonly string $creatorId,
        public readonly string $productId,
    ) {}

    public function handle(WebhookSignatureService $signatureService): void
    {
        $payload = $this->buildPayload();
        $jsonPayload = json_encode($payload);
        $signature = $signatureService->sign($jsonPayload);
        $webhookUrl = config('giftflow.webhook.url');

        Log::info('Webhook sending', [
            'event_id' => $this->eventId,
            'url' => $webhookUrl,
            'attempt' => $this->attempts(),
        ]);

        $response = Http::timeout(self::TIMEOUT_SECONDS)
            ->withBody($jsonPayload, 'application/json')
            ->withHeaders(['X-GiftFlow-Signature' => $signature])
            ->post($webhookUrl);

        if ($response->successful()) {
            Log::info('Webhook delivered', [
                'event_id' => $this->eventId,
                'status' => $response->status(),
            ]);
            return;
        }

        Log::warning('Webhook failed', [
            'event_id' => $this->eventId,
            'status' => $response->status(),
            'response' => $response->body(),
            'attempt' => $this->attempts(),
        ]);

        throw new \Exception("Webhook failed: " . $response->status());
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('Webhook permanently failed', [
            'event_id' => $this->eventId,
            'code' => $this->code,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    private function buildPayload(): array
    {
        return [
            'event_id' => $this->eventId,
            'type' => 'giftcard.redeemed',
            'data' => [
                'code' => $this->code,
                'email' => $this->email,
                'creator_id' => $this->creatorId,
                'product_id' => $this->productId,
            ],
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
