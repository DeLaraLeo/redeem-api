<?php

namespace App\Http\Controllers;

use App\Http\Requests\RedeemRequest;
use App\Http\Resources\RedeemResource;
use App\Services\RedeemService;

class RedeemController extends Controller 
{
    public function __construct(
        private RedeemService $redeemService
    ) {
    }
    
    public function redeem(RedeemRequest $request): RedeemResource
    {
        $validated = $request->validated();
        
        $result = $this->redeemService->redeem(
            code: $validated['code'],
            email: $validated['user']['email']
        );
        
        return RedeemResource::make($result);
    }
}