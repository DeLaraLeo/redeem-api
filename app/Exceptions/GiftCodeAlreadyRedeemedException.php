<?php

namespace App\Exceptions;

use Exception;

class GiftCodeAlreadyRedeemedException extends Exception
{
    public function __construct(string $code)
    {
        parent::__construct("Gift code '{$code}' has already been redeemed.");
    }

    public function render()
    {
        return response()->json([
            'error' => 'already_redeemed',
            'message' => $this->getMessage(),
        ], 409);
    }
}
