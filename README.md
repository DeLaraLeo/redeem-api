# GiftFlow Redeem API

Gift card redemption API with webhooks.

## Flow

```mermaid
sequenceDiagram
    participant User
    participant API as API Laravel
    participant Redis as Redis Cache
    participant Queue as Queue Worker
    participant Webhook as Webhook Receiver

    User->>API: POST /api/redeem {code, email}
    API->>Redis: Find gift code
    Redis-->>API: Code data
    
    alt Valid and available
        API->>Redis: Mark as redeemed
        API->>Redis: Save redemption record
        API->>Queue: Enqueue SendWebhookJob
        API-->>User: 200 OK {status: redeemed}
        
        Note over Queue: Worker processes queue
        Queue->>Webhook: POST signed webhook
        Webhook->>Redis: Check if event_id received
        Webhook-->>Queue: 200 OK
    else Already redeemed
        API-->>User: 409 Conflict
    else Not found
        API-->>User: 404 Not Found
    end
```

## Local Setup

```bash
# Start containers
docker compose up --build -d

# Generate application key
docker compose exec redeem-api php artisan key:generate

# Seed initial data
docker compose exec redeem-api php artisan giftflow:seed
```

## Test

```bash
curl -X POST http://localhost:8000/api/redeem \
  -H "Content-Type: application/json" \
  -d '{"code":"GFLOW-TEST-0001","user":{"email":"test@example.com"}}'
```

## URLs

| Service | URL |
|---------|-----|
| API | http://localhost:8000 |
| Redis Commander | http://localhost:8081 |

## Real-time Logs

```bash
docker compose logs -f redeem-queue redeem-api
```

## Reset Data

```bash
docker compose exec redeem-api php artisan giftflow:seed --fresh
```
