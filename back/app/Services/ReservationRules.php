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
    public function check(Machine $machine, Carbon $from, Carbon $to, ?int $ignoredReservationId = null): array
    {
        return [
            ...$this->overlaps($machine, $from, $to, $ignoredReservationId),
            ...$this->workshop($machine, $from, $to),
            ...$this->vgp($machine, $to),
        ];
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
