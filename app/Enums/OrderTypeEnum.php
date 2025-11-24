<?php

namespace App\Enums;

enum OrderTypeEnum: string
{
    case TAKE_AWAY = 'take_away';
    case DELIVERY = 'delivery';
}
