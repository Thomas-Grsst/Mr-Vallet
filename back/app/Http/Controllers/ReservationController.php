<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Machine;
use App\Models\Reservation;
use App\Services\ReservationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class ReservationController extends Controller
{
    public function __construct(private ReservationRules $rules) {}

    public function index(): JsonResponse
    {
        $reservations = Reservation::query()
            ->whereNull('cancelled_at')
            ->with(['machine.agency', 'enteredBy'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Reservation $reservation) => $this->present($reservation));

        return response()->json($reservations);
    }

    public function history(): JsonResponse
    {
        $today = ReservationRules::today();

        $reservations = Reservation::query()
            ->with(['machine.agency', 'enteredBy', 'cancelledBy'])
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (Reservation $reservation) => [
                ...$this->present($reservation),
                'status' => $reservation->statusOn($today)->value,
                'status_label' => $reservation->statusOn($today)->label(),
                'cancelled_at' => $reservation->cancelled_at?->toDateString(),
                'cancelled_by' => $reservation->isCancelled() ? ($reservation->cancelledBy?->name ?? UserRole::Director->label()) : null,
            ]);

        return response()->json($reservations);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        abort_unless($request->user()->role->canBook(), Response::HTTP_FORBIDDEN, 'Seules les agences peuvent réserver.');

        $machine = Machine::query()->where('ref', $request->string('machine_ref'))->firstOrFail();
        $from = Carbon::parse($request->string('starts_at'));
        $to = Carbon::parse($request->string('ends_at'));

        $enteringAgencyId = $request->user()->role->choosesEnteringAgency()
            ? $request->integer('agency_id')
            : $request->user()->agency_id;

        $violations = $this->rules->check($machine, $from, $to, bookingAgencyId: $enteringAgencyId);

        if ($violations !== []) {
            return response()->json([
                'message' => 'Réservation refusée',
                'violations' => $violations,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reservation = $machine->reservations()->create([
            'client' => $request->string('client')->trim()->toString(),
            'starts_at' => $from->toDateString(),
            'ends_at' => $to->toDateString(),
            'entered_by_agency_id' => $enteringAgencyId,
        ]);

        return response()->json(
            $this->present($reservation->load(['machine.agency', 'enteredBy'])),
            Response::HTTP_CREATED,
        );
    }

    public function destroy(Request $request, Reservation $reservation): Response
    {
        abort_unless($request->user()->role->canBook(), Response::HTTP_FORBIDDEN, 'Seules les agences peuvent annuler une réservation.');
        abort_if($reservation->isCancelled(), Response::HTTP_CONFLICT, 'Cette réservation est déjà annulée.');

        $reservation->update([
            'cancelled_at' => ReservationRules::today()->toDateString(),
            'cancelled_by_agency_id' => $request->user()->agency_id,
        ]);

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function present(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'machine_ref' => $reservation->machine->ref,
            'machine_type' => $reservation->machine->type,
            'machine_agency' => $reservation->machine->agency->name,
            'client' => $reservation->client,
            'starts_at' => $reservation->starts_at->toDateString(),
            'ends_at' => $reservation->ends_at->toDateString(),
            'entered_by' => $reservation->enteredBy->name,
        ];
    }
}
