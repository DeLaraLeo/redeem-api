<?php

namespace App\Exceptions;

use Exception;

class GiftCodeNotFoundException extends Exception
{
    public function __construct(string $code)
    {
        parent::__construct("Gift code '{$code}' not found.");
    }

    public function render()
    {
        return response()->json([
            'error' => 'not_found',
            'message' => $this->getMessage(),
        ], 404);
    }
}
