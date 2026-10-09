<?php

namespace App\Enums;

enum UserRole: string
{
    case Agency = 'agency';
    case Workshop = 'workshop';
    case Sales = 'sales';

    public function label(): string
    {
        return match ($this) {
            self::Agency => 'Agence',
            self::Workshop => 'Atelier',
            self::Sales => 'Commercial',
        };
    }

    public function canBook(): bool
    {
        return $this === self::Agency;
    }

    public function canMaintain(): bool
    {
        return $this === self::Workshop;
    }
}
