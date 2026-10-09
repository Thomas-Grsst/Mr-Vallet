<?php

namespace App\Services;

use App\Enums\Violation;
use App\Models\Machine;
use App\Models\WorkshopPeriod;
use Illuminate\Support\Carbon;

class ReservationRules
{
    public static function today(): Carbon
    {
        return Carbon::parse(config('vallet.today'))->startOfDay();
    }

    /** @return list<array{code: string, message: string}> */
    public function check(Machine $machine, Carbon $from, Carbon $to, ?int $ignoredReservationId = null, ?int $bookingAgencyId = null): array
    {
        $occupation = [
            ...$this->overlaps($machine, $from, $to, $ignoredReservationId),
            ...$this->workshop($machine, $from, $to),
        ];

        return [
            ...$occupation,
            ...$this->transfer($machine, $from, $bookingAgencyId, $occupation, $ignoredReservationId),
            ...$this->vgp($machine, $to),
        ];
    }

    /**
     * @param  list<array{code: string, message: string}>  $alreadyReported
     * @return list<array{code: string, message: string}>
     */
    public function transfer(Machine $machine, Carbon $from, ?int $bookingAgencyId, array $alreadyReported = [], ?int $ignoredReservationId = null): array
    {
        if ($bookingAgencyId === null || $bookingAgencyId === $machine->agency_id) {
            return [];
        }

        $eve = $from->copy()->subDay();
        $reported = array_column($alreadyReported, 'message');

        return collect([
            ...$this->overlaps($machine, $eve, $eve, $ignoredReservationId),
            ...$this->workshop($machine, $eve, $eve),
        ])
            ->reject(fn (array $violation) => in_array($violation['message'], $reported, true))
            ->map(fn (array $violation) => [
                'code' => Violation::Transfer->value,
                'message' => "Transfert depuis {$machine->agency->name} impossible : la machine doit être libre la veille du départ ({$eve->format('d/m/Y')}) — {$violation['message']}",
            ])
            ->values()
            ->all();
    }

    /** @return list<array{code: string, message: string}> */
    public function overlaps(Machine $machine, Carbon $from, Carbon $to, ?int $ignoredReservationId = null): array
    {
        return $machine->reservations()
            ->whereNull('cancelled_at')
            ->where('starts_at', '<=', $to->toDateString())
            ->where('ends_at', '>=', $from->toDateString())
            ->when($ignoredReservationId, fn ($query) => $query->whereKeyNot($ignoredReservationId))
            ->orderBy('starts_at')
            ->get()
            ->map(fn ($reservation) => [
                'code' => Violation::Overlap->value,
                'message' => "Période déjà occupée par {$reservation->client} du {$reservation->starts_at->format('d/m/Y')} au {$reservation->ends_at->format('d/m/Y')}",
            ])
            ->values()
            ->all();
    }

    /** @return list<array{code: string, message: string}> */
    public function workshop(Machine $machine, Carbon $from, Carbon $to): array
    {
        return $machine->workshopPeriods
            ->filter(fn (WorkshopPeriod $period) => $period->overlaps($from, $to))
            ->map(fn (WorkshopPeriod $period) => [
                'code' => Violation::Workshop->value,
                'message' => "Machine en atelier {$period->describe()}",
            ])
            ->values()
            ->all();
    }

    /** @return list<array{code: string, message: string}> */
    public function vgp(Machine $machine, Carbon $to): array
    {
        if (! $machine->requiresVgp()) {
            return [];
        }

        $expiresAt = $machine->vgpExpiresAt();

        if ($expiresAt !== null && $to->lt($expiresAt)) {
            return [];
        }

        return [[
            'code' => Violation::VgpExpired->value,
            'message' => "VGP non à jour, contacter l'atelier",
        ]];
    }
}
