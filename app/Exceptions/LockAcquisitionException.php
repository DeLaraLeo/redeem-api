<?php

namespace App\Exceptions;

use Exception;

class LockAcquisitionException extends Exception
{
    public function __construct(string $resource)
    {
        parent::__construct("Unable to acquire lock for resource: {$resource}");
    }

    public function render()
    {
        return response()->json([
            'error' => 'service_busy',
            'message' => 'The service is temporarily busy. Please retry.',
        ], 503);
    }
}
