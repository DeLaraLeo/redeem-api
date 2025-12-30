<?php

namespace App\Enums;

enum GiftCodeStatus: string
{
    case Available = 'available';
    case Redeemed = 'redeemed';
}
