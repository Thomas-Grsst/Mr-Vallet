<?php

namespace App\Enums;

enum ReservationEventType: string
{
    case Modified = 'modified';
    case Cancelled = 'cancelled';
}
