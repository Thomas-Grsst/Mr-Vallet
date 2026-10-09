<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\User;

class ReservationPermissions
{
    public function cancelDenial(User $user, Reservation $reservation): ?string
    {
        if ($user->role === UserRole::Director || ($user->role === UserRole::AgencyManager && $this->concerns($user, $reservation))) {
            return null;
        }

        $entering = $reservation->enteredBy->name;
        $owner = $reservation->machine->agency->name;

        return $entering === $owner
            ? "Annulation réservée au responsable de {$entering}"
            : "Annulation réservée au responsable de {$entering} (agence de saisie) ou de {$owner} (agence de la machine)";
    }

    public function ignoresCancellationDeadline(User $user, Reservation $reservation): bool
    {
        return $user->role === UserRole::Director && ! $reservation->isFinishedOn(ReservationRules::today());
    }

    public function modifyDenial(User $user, Reservation $reservation): ?string
    {
        $isEnteringAgency = $user->agency_id === $reservation->entered_by_agency_id;

        if (
            $user->role === UserRole::Director
            || ($user->role === UserRole::Agency && $isEnteringAgency)
            || ($user->role === UserRole::AgencyManager && $this->concerns($user, $reservation))
        ) {
            return null;
        }

        $entering = $reservation->enteredBy->name;
        $owner = $reservation->machine->agency->name;

        return $entering === $owner
            ? "Modification réservée à l'agence {$entering}"
            : "Modification réservée à l'agence {$entering} (agence de saisie) et au responsable de {$owner} (agence de la machine)";
    }

    private function concerns(User $user, Reservation $reservation): bool
    {
        return in_array($user->agency_id, [$reservation->entered_by_agency_id, $reservation->machine->agency_id], true);
    }
}
