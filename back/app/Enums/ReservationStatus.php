<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'à venir',
            self::Ongoing => 'en cours',
            self::Finished => 'terminée',
            self::Cancelled => 'annulée',
        };
    }
}
