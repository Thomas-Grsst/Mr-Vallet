<?php

namespace App\Enums;

enum UserRole: string
{
    case Agency = 'agency';
    case Workshop = 'workshop';
    case Sales = 'sales';
    case Director = 'director';

    public function label(): string
    {
        return match ($this) {
            self::Agency => 'Agence',
            self::Workshop => 'Atelier',
            self::Sales => 'Commercial',
            self::Director => 'Direction',
        };
    }

    public function canBook(): bool
    {
        return $this === self::Agency || $this === self::Director;
    }

    public function canMaintain(): bool
    {
        return $this === self::Workshop || $this === self::Director;
    }

    public function canManageKeyAccounts(): bool
    {
        return $this === self::Director || $this === self::Sales;
    }

    public function choosesEnteringAgency(): bool
    {
        return $this === self::Director;
    }
}
