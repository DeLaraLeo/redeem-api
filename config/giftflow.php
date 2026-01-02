<?php

return [
    'lock' => [
        'timeout' => (int) env('GIFTFLOW_LOCK_TIMEOUT', 3),
        'wait' => (int) env('GIFTFLOW_LOCK_WAIT', 2),
    ],
    'webhook' => [
        'secret' => env('GIFTFLOW_WEBHOOK_SECRET', 'default-secret'),
        'url' => env('GIFTFLOW_WEBHOOK_URL'),
    ],
];
