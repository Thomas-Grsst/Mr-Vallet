<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\Reservation;
use App\Models\WorkshopPeriod;
use Illuminate\Support\Carbon;

class MachineTimeline
{
    public const HORIZON_DAYS = 60;

    public function __construct(private ReservationRules $rules) {}

    /** @return array{occupations: list<array<string, string|null>>, free_windows: list<array{starts_at: string, ends_at: string|null}>} */
    public function for(Machine $machine, ?int $bookingAgencyId): array
    {
        return [
            'occupations' => $this->occupations($machine),
            'free_windows' => $this->freeWindows($machine, $bookingAgencyId),
        ];
    }

    /** @return list<array<string, string|null>> */
    private function occupations(Machine $machine): array
    {
        $today = ReservationRules::today();
        $horizon = $today->copy()->addDays(self::HORIZON_DAYS - 1);

        $reservations = $machine->reservations
            ->filter(fn (Reservation $reservation) => ! $reservation->isCancelled()
                && $reservation->ends_at->gte($today)
                && $reservation->starts_at->lte($horizon))
            ->map(fn (Reservation $reservation) => [
                'kind' => 'reservation',
                'starts_at' => $reservation->starts_at->toDateString(),
                'ends_at' => $reservation->ends_at->toDateString(),
                'label' => $reservation->client,
            ]);

        $workshop = $machine->workshopPeriods
            ->filter(fn (WorkshopPeriod $period) => $period->ends_at->gte($today) && $period->starts_at->lte($horizon))
            ->map(fn (WorkshopPeriod $period) => [
                'kind' => 'workshop',
                'starts_at' => $period->starts_at->toDateString(),
                'ends_at' => $period->ends_at->toDateString(),
                'label' => $period->reason,
            ]);

        $expiresAt = $machine->vgpExpiresAt();
        $vgp = $machine->requiresVgp() && ($expiresAt === null || $expiresAt->lte($horizon))
            ? [['kind' => 'vgp', 'starts_at' => $expiresAt?->toDateString(), 'ends_at' => null, 'label' => null]]
            : [];

        return $reservations
            ->concat($workshop)
            ->concat($vgp)
            ->sortBy(fn (array $occupation) => $occupation['starts_at'] ?? '')
            ->values()
            ->all();
    }

    /** @return list<array{starts_at: string, ends_at: string|null}> */
    private function freeWindows(Machine $machine, ?int $bookingAgencyId): array
    {
        $windows = [];
        $start = null;
        $day = ReservationRules::today();

        for ($offset = 0; $offset < self::HORIZON_DAYS; $offset++) {
            $isFree = $this->rules->check($machine, $day, $day, bookingAgencyId: $bookingAgencyId) === [];

            if ($isFree && $start === null) {
                $start = $day->copy();
            }

            if (! $isFree && $start !== null) {
                $windows[] = ['starts_at' => $start->toDateString(), 'ends_at' => $day->copy()->subDay()->toDateString()];
                $start = null;
            }

            $day = $day->copy()->addDay();
        }

        if ($start !== null) {
            $windows[] = ['starts_at' => $start->toDateString(), 'ends_at' => null];
        }

        return $windows;
    }
}
