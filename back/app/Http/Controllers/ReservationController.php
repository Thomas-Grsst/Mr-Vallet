<?php

namespace App\Http\Controllers;

use App\Enums\ReservationEventType;
use App\Enums\UserRole;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\KeyAccount;
use App\Models\Machine;
use App\Models\Reservation;
use App\Models\ReservationEvent;
use App\Services\ReservationPermissions;
use App\Services\ReservationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function __construct(private ReservationRules $rules, private ReservationPermissions $permissions) {}

    public function index(): JsonResponse
    {
        $reservations = Reservation::query()
            ->whereNull('cancelled_at')
            ->with(['machine.agency', 'enteredBy', 'modifiedBy'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Reservation $reservation) => $this->present($reservation));

        return response()->json($reservations);
    }

    public function history(): JsonResponse
    {
        $today = ReservationRules::today();

        $reservations = Reservation::query()
            ->with(['machine.agency', 'enteredBy', 'cancelledBy', 'modifiedBy', 'events.user.agency'])
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (Reservation $reservation) => [
                ...$this->present($reservation),
                'events' => $reservation->events->map(fn (ReservationEvent $event) => [
                    'type' => $event->type->value,
                    'occurred_on' => $event->occurred_on->toDateString(),
                    'description' => $event->describe(),
                ])->values(),
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

        $purchaseOrder = $request->filled('purchase_order') ? $request->string('purchase_order')->trim()->toString() : null;

        $violations = [
            ...$this->rules->purchaseOrder($request->string('client')->toString(), $purchaseOrder),
            ...$this->rules->check($machine, $from, $to, bookingAgencyId: $enteringAgencyId),
        ];

        if ($violations !== []) {
            return response()->json([
                'message' => 'Réservation refusée',
                'violations' => $violations,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reservation = $machine->reservations()->create([
            'client' => KeyAccount::matching($request->string('client')->toString())?->name ?? $request->string('client')->trim()->toString(),
            'purchase_order' => $purchaseOrder,
            'starts_at' => $from->toDateString(),
            'ends_at' => $to->toDateString(),
            'entered_by_agency_id' => $enteringAgencyId,
        ]);

        return response()->json(
            $this->present($reservation->load(['machine.agency', 'enteredBy', 'modifiedBy'])),
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation): JsonResponse
    {
        abort_unless($request->user()->role->canBook(), Response::HTTP_FORBIDDEN, 'Seules les agences et la Direction peuvent modifier une réservation.');
        $denial = $this->permissions->modifyDenial($request->user(), $reservation);
        abort_if($denial !== null, Response::HTTP_FORBIDDEN, (string) $denial);
        abort_if($reservation->isCancelled(), Response::HTTP_CONFLICT, 'Une réservation annulée ne peut pas être modifiée.');

        $today = ReservationRules::today();
        $from = Carbon::parse($request->string('starts_at'));
        $to = Carbon::parse($request->string('ends_at'));
        $startChanges = ! $from->equalTo($reservation->starts_at);

        if (! $reservation->isCancellableOn($today) && ($startChanges || $to->lt($reservation->ends_at))) {
            return $this->refused([[
                'code' => 'locked',
                'message' => "Moins de 48 h avant le début : la location peut seulement être prolongée (début le {$reservation->starts_at->format('d/m/Y')}, fin au plus tôt le {$reservation->ends_at->format('d/m/Y')})",
            ]]);
        }

        if ($startChanges && $from->lt($today)) {
            return $this->refused([[
                'code' => 'past',
                'message' => "On ne peut pas réserver dans le passé : la date de début doit être le {$today->format('d/m/Y')} ou après.",
            ]]);
        }

        $purchaseOrder = $request->filled('purchase_order')
            ? $request->string('purchase_order')->trim()->toString()
            : $reservation->purchase_order;

        $violations = [
            ...$this->rules->purchaseOrder($reservation->client, $purchaseOrder),
            ...$this->rules->check($reservation->machine, $from, $to, $reservation->id, $reservation->entered_by_agency_id),
        ];

        if ($violations !== []) {
            return $this->refused($violations);
        }

        DB::transaction(function () use ($reservation, $request, $today, $from, $to, $purchaseOrder) {
            $reservation->events()->create([
                'type' => ReservationEventType::Modified,
                'occurred_on' => $today->toDateString(),
                'user_id' => $request->user()->id,
                'previous_starts_at' => $reservation->starts_at->toDateString(),
                'previous_ends_at' => $reservation->ends_at->toDateString(),
                'new_starts_at' => $from->toDateString(),
                'new_ends_at' => $to->toDateString(),
                'previous_purchase_order' => $reservation->purchase_order,
                'new_purchase_order' => $purchaseOrder,
            ]);

            $reservation->update([
                'purchase_order' => $purchaseOrder,
                'starts_at' => $from->toDateString(),
                'ends_at' => $to->toDateString(),
                'modified_at' => $today->toDateString(),
                'modified_by_agency_id' => $request->user()->agency_id,
            ]);
        });

        return response()->json($this->present($reservation->fresh(['machine.agency', 'enteredBy', 'modifiedBy'])));
    }

    /** @param list<array{code: string, message: string}> $violations */
    private function refused(array $violations): JsonResponse
    {
        return response()->json([
            'message' => 'Modification refusée',
            'violations' => $violations,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function destroy(Request $request, Reservation $reservation): Response
    {
        abort_unless($request->user()->role->canBook(), Response::HTTP_FORBIDDEN, 'Seules les agences peuvent annuler une réservation.');
        abort_if($reservation->isCancelled(), Response::HTTP_CONFLICT, 'Cette réservation est déjà annulée.');
        abort_unless(
            $reservation->isCancellableOn(ReservationRules::today())
                || $this->permissions->ignoresCancellationDeadline($request->user(), $reservation),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            "Annulation impossible : la location commence le {$reservation->starts_at->format('d/m/Y')}, il fallait annuler au plus tard le {$reservation->cancellableUntil()->format('d/m/Y')}. Le client doit garder la réservation.",
        );
        $denial = $this->permissions->cancelDenial($request->user(), $reservation);
        abort_if($denial !== null, Response::HTTP_FORBIDDEN, (string) $denial);

        DB::transaction(function () use ($reservation, $request) {
            $reservation->events()->create([
                'type' => ReservationEventType::Cancelled,
                'occurred_on' => ReservationRules::today()->toDateString(),
                'user_id' => $request->user()->id,
            ]);

            $reservation->update([
                'cancelled_at' => ReservationRules::today()->toDateString(),
                'cancelled_by_agency_id' => $request->user()->agency_id,
            ]);
        });

        return response()->noContent();
    }

    /** @return array{can_cancel: bool, can_modify: bool, cancel_denied_reason: ?string, cancel_beyond_deadline: bool} */
    private function rightsOf(Reservation $reservation): array
    {
        $user = request()->user();

        if (! $user->role->canBook() || $reservation->isCancelled()) {
            return ['can_cancel' => false, 'can_modify' => false, 'cancel_denied_reason' => null, 'cancel_beyond_deadline' => false];
        }

        $cancelDenial = $this->permissions->cancelDenial($user, $reservation);
        $withinDeadline = $reservation->isCancellableOn(ReservationRules::today());
        $beyondDeadline = ! $withinDeadline && $this->permissions->ignoresCancellationDeadline($user, $reservation);

        return [
            'can_cancel' => $cancelDenial === null && ($withinDeadline || $beyondDeadline),
            'can_modify' => $this->permissions->modifyDenial($user, $reservation) === null,
            'cancel_denied_reason' => $cancelDenial,
            'cancel_beyond_deadline' => $cancelDenial === null && $beyondDeadline,
        ];
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
            'purchase_order' => $reservation->purchase_order,
            'starts_at' => $reservation->starts_at->toDateString(),
            'ends_at' => $reservation->ends_at->toDateString(),
            'entered_by' => $reservation->enteredBy->name,
            'cancellable_until' => $reservation->cancellableUntil()->toDateString(),
            'cancellable' => $reservation->isCancellableOn(ReservationRules::today()),
            ...$this->rightsOf($reservation),
            'modified_at' => $reservation->modified_at?->toDateString(),
            'modified_by' => $reservation->modified_at ? ($reservation->modifiedBy?->name ?? UserRole::Director->label()) : null,
        ];
    }
}
