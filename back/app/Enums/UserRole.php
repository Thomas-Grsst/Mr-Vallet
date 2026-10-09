<?php

namespace App\Enums;

enum UserRole: string
{
    case Agency = 'agency';
    case AgencyManager = 'agency_manager';
    case Workshop = 'workshop';
    case Sales = 'sales';
    case Director = 'director';

    public function label(): string
    {
        return match ($this) {
            self::Agency => 'Agence',
            self::AgencyManager => "Responsable d'agence",
            self::Workshop => 'Atelier',
            self::Sales => 'Commercial',
            self::Director => 'Direction',
        };
    }

    public function canBook(): bool
    {
        return in_array($this, [self::Agency, self::AgencyManager, self::Director], true);
    }

    public function canCancelOtherAgencies(): bool
    {
        return $this === self::AgencyManager || $this === self::Director;
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
