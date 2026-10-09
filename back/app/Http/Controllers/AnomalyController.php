<?php

namespace App\Http\Controllers;

use App\Enums\Violation;
use App\Models\Reservation;
use App\Models\WorkshopPeriod;
use App\Services\ReservationRules;
use Illuminate\Http\JsonResponse;

class AnomalyController extends Controller
{
    public function __construct(private ReservationRules $rules) {}

    public function __invoke(): JsonResponse
    {
        $reservations = Reservation::query()->whereNull('cancelled_at')->with('machine.workshopPeriods')->orderBy('starts_at')->get();

        $overlaps = $reservations
            ->flatMap(fn (Reservation $first) => $reservations
                ->filter(fn (Reservation $second) => $first->machine_id === $second->machine_id
                    && $first->id < $second->id
                    && $first->starts_at->lte($second->ends_at)
                    && $first->ends_at->gte($second->starts_at))
                ->map(fn (Reservation $second) => [
                    'code' => Violation::Overlap->value,
                    'machine_ref' => $first->machine->ref,
                    'reservation_ids' => [$first->id, $second->id],
                    'message' => "{$first->machine->ref} : double réservation, {$first->client} du {$first->starts_at->format('d/m')} au {$first->ends_at->format('d/m')} et {$second->client} du {$second->starts_at->format('d/m')} au {$second->ends_at->format('d/m')}",
                ]));

        $vgp = $reservations
            ->filter(fn (Reservation $reservation) => $this->rules->vgp($reservation->machine, $reservation->ends_at) !== [])
            ->map(fn (Reservation $reservation) => [
                'code' => Violation::VgpExpired->value,
                'machine_ref' => $reservation->machine->ref,
                'reservation_ids' => [$reservation->id],
                'message' => "{$reservation->machine->ref} : réservée pour {$reservation->client} du {$reservation->starts_at->format('d/m')} au {$reservation->ends_at->format('d/m')} alors que sa VGP n'est pas à jour",
            ]);

        $workshop = $reservations->flatMap(fn (Reservation $reservation) => $reservation->machine->workshopPeriods
            ->filter(fn (WorkshopPeriod $period) => $period->overlaps($reservation->starts_at, $reservation->ends_at))
            ->map(fn (WorkshopPeriod $period) => [
                'code' => Violation::Workshop->value,
                'machine_ref' => $reservation->machine->ref,
                'reservation_ids' => [$reservation->id],
                'message' => "{$reservation->machine->ref} : réservée pour {$reservation->client} du {$reservation->starts_at->format('d/m')} au {$reservation->ends_at->format('d/m')} pendant un passage en atelier {$period->describe()}",
            ]));

        $purchaseOrders = $reservations
            ->filter(fn (Reservation $reservation) => $this->rules->purchaseOrder($reservation->client, $reservation->purchase_order) !== [])
            ->map(fn (Reservation $reservation) => [
                'code' => Violation::MissingPurchaseOrder->value,
                'machine_ref' => $reservation->machine->ref,
                'reservation_ids' => [$reservation->id],
                'message' => "{$reservation->machine->ref} : réservée pour {$reservation->client} du {$reservation->starts_at->format('d/m')} au {$reservation->ends_at->format('d/m')} sans bon de commande alors que c'est un grand compte",
            ]);

        return response()->json($overlaps->concat($workshop)->concat($vgp)->concat($purchaseOrders)->values());
    }
}
