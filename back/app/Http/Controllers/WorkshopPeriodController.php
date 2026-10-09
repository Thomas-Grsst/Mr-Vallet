<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkshopPeriodRequest;
use App\Models\Machine;
use App\Models\Reservation;
use App\Models\WorkshopPeriod;
use App\Services\ReservationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class WorkshopPeriodController extends Controller
{
    public function __construct(private ReservationRules $rules) {}

    public function store(StoreWorkshopPeriodRequest $request, Machine $machine): JsonResponse
    {
        abort_unless($request->user()->role->canMaintain(), Response::HTTP_FORBIDDEN, "Seul l'atelier peut gérer les passages en atelier.");

        $from = Carbon::parse($request->string('starts_at'));
        $to = Carbon::parse($request->string('ends_at'));

        $conflicts = $machine->workshopPeriods->filter(fn (WorkshopPeriod $period) => $period->overlaps($from, $to));

        if ($conflicts->isNotEmpty()) {
            return response()->json([
                'message' => 'Passage en atelier refusé',
                'violations' => $conflicts->map(fn (WorkshopPeriod $period) => [
                    'code' => 'workshop_overlap',
                    'message' => "Un passage en atelier existe déjà {$period->describe()}",
                ])->values(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $machine->workshopPeriods()->create([
            'starts_at' => $from->toDateString(),
            'ends_at' => $to->toDateString(),
            'reason' => $request->filled('reason') ? $request->string('reason')->trim()->toString() : null,
        ]);

        $impacted = $machine->reservations()
            ->whereNull('cancelled_at')
            ->where('starts_at', '<=', $to->toDateString())
            ->where('ends_at', '>=', $from->toDateString())
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Reservation $reservation) => "{$reservation->client} du {$reservation->starts_at->format('d/m/Y')} au {$reservation->ends_at->format('d/m/Y')}");

        return response()->json([
            'machine' => MachineController::present($machine->fresh(['agency', 'workshopPeriods']), $this->rules),
            'impacted_reservations' => $impacted,
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, WorkshopPeriod $period): Response
    {
        abort_unless($request->user()->role->canMaintain(), Response::HTTP_FORBIDDEN, "Seul l'atelier peut gérer les passages en atelier.");

        $today = ReservationRules::today();

        abort_if($period->ends_at->lt($today), Response::HTTP_CONFLICT, 'Ce passage en atelier est déjà terminé.');

        if ($period->starts_at->lt($today)) {
            $period->update(['ends_at' => $today->copy()->subDay()->toDateString()]);
        } else {
            $period->delete();
        }

        return response()->noContent();
    }
}
