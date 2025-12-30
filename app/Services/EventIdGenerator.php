<?php

namespace App\Services;

class EventIdGenerator
{
    public function generate(string $code, string $email): string
    {
        $hash = hash('sha256', $code . '|' . $email);
        
        return 'evt_' . substr($hash, 0, 32);
    }
}
