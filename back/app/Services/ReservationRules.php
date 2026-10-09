<?php

namespace App\Services;

use App\Enums\Violation;
use App\Models\KeyAccount;
use App\Models\Machine;
use App\Models\Reservation;
use App\Models\WorkshopPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReservationRules
{
    public static function today(): Carbon
    {
        return Carbon::parse(config('vallet.today'))->startOfDay();
    }

    /** @return list<array{code: string, message: string}> */
    public function check(Machine $machine, Carbon $from, Carbon $to, ?int $ignoredReservationId = null, ?int $bookingAgencyId = null): array
    {
        return [
            ...$this->overlaps($machine, $from, $to, $ignoredReservationId, explainReturnDay: true),
            ...$this->workshop($machine, $from, $to),
            ...$this->transfer($machine, $from, $to, $bookingAgencyId, $ignoredReservationId),
            ...$this->vgp($machine, $to),
        ];
    }

    /** @return list<array{code: string, message: string}> */
    public function transfer(Machine $machine, Carbon $from, Carbon $to, ?int $bookingAgencyId, ?int $ignoredReservationId = null): array
    {
        if ($bookingAgencyId === null || $bookingAgencyId === $machine->agency_id) {
            return [];
        }

        $eve = $from->copy()->subDay();

        $reservations = $this->overlappingReservations($machine, $eve, $eve, $ignoredReservationId)
            ->reject(fn (Reservation $reservation) => $reservation->ends_at->gte($from))
            ->map(fn (Reservation $reservation) => $this->occupiedMessage($reservation));

        $periods = $machine->workshopPeriods
            ->filter(fn (WorkshopPeriod $period) => $period->overlaps($eve, $eve) && ! $period->overlaps($from, $to))
            ->map(fn (WorkshopPeriod $period) => "Machine en atelier {$period->describe()}");

        return $reservations->concat($periods)
            ->map(fn (string $cause) => [
                'code' => Violation::Transfer->value,
                'message' => "Transfert depuis {$machine->agency->name} impossible : la machine doit être libre la veille du départ ({$eve->format('d/m/Y')}) — {$cause}",
            ])
            ->values()
            ->all();
    }

    /** @return list<array{code: string, message: string}> */
    public function overlaps(Machine $machine, Carbon $from, Carbon $to, ?int $ignoredReservationId = null, bool $explainReturnDay = false): array
    {
        return $this->overlappingReservations($machine, $from, $to, $ignoredReservationId)
            ->map(fn (Reservation $reservation) => [
                'code' => Violation::Overlap->value,
                'message' => $explainReturnDay && $reservation->ends_at->equalTo($from) && $reservation->starts_at->lt($from)
                    ? "Retour de {$reservation->client} le {$reservation->ends_at->format('d/m/Y')} : la machine est nettoyée et contrôlée ce jour-là, elle est relouable à partir du {$reservation->ends_at->copy()->addDay()->format('d/m/Y')}"
                    : $this->occupiedMessage($reservation),
            ])
            ->values()
            ->all();
    }

    /** @return Collection<int, Reservation> */
    private function overlappingReservations(Machine $machine, Carbon $from, Carbon $to, ?int $ignoredReservationId): Collection
    {
        return $machine->reservations
            ->filter(fn (Reservation $reservation) => ! $reservation->isCancelled()
                && $reservation->id !== $ignoredReservationId
                && $reservation->starts_at->lte($to)
                && $reservation->ends_at->gte($from))
            ->sortBy('starts_at')
            ->values()
            ->toBase();
    }

    private function occupiedMessage(Reservation $reservation): string
    {
        return "Période déjà occupée par {$reservation->client} du {$reservation->starts_at->format('d/m/Y')} au {$reservation->ends_at->format('d/m/Y')}";
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
    public function purchaseOrder(string $client, ?string $purchaseOrder): array
    {
        $keyAccount = KeyAccount::matching($client);

        if ($keyAccount === null || ($purchaseOrder !== null && trim($purchaseOrder) !== '')) {
            return [];
        }

        return [[
            'code' => Violation::MissingPurchaseOrder->value,
            'message' => "{$keyAccount->name} est un grand compte : le numéro de bon de commande est obligatoire",
        ]];
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
