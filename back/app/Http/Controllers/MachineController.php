<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchMachinesRequest;
use App\Http\Requests\UpdateVgpRequest;
use App\Models\Machine;
use App\Models\WorkshopPeriod;
use App\Services\ReservationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class MachineController extends Controller
{
    public function __construct(private ReservationRules $rules) {}

    public function index(SearchMachinesRequest $request): JsonResponse
    {
        $from = $request->filled('from') ? Carbon::parse($request->string('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->string('to')) : null;
        $bookingAgencyId = $request->user()->agency_id;

        $machines = Machine::query()
            ->with(['agency', 'workshopPeriods'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->orderBy('type')
            ->orderBy('ref')
            ->get()
            ->map(function (Machine $machine) use ($from, $to, $bookingAgencyId) {
                $violations = $from && $to ? $this->rules->check($machine, $from, $to, bookingAgencyId: $bookingAgencyId) : null;

                return [
                    ...self::present($machine, $this->rules),
                    'available' => $violations === null ? null : $violations === [],
                    'reasons' => $violations === null ? [] : array_column($violations, 'message'),
                ];
            })
            ->sortByDesc('available')
            ->values();

        return response()->json($machines);
    }

    public function types(): JsonResponse
    {
        return response()->json(Machine::query()->distinct()->orderBy('type')->pluck('type'));
    }

    public function updateVgp(UpdateVgpRequest $request, Machine $machine): JsonResponse
    {
        abort_unless($request->user()->role->canMaintain(), Response::HTTP_FORBIDDEN, "Seul l'atelier peut mettre à jour la VGP.");

        $machine->update(['last_vgp_at' => $request->input('last_vgp_at')]);

        return response()->json(self::present($machine->load(['agency', 'workshopPeriods']), $this->rules));
    }

    /** @return array<string, mixed> */
    public static function present(Machine $machine, ReservationRules $rules): array
    {
        $today = ReservationRules::today();

        return [
            'ref' => $machine->ref,
            'type' => $machine->type,
            'agency' => $machine->agency->name,
            'requires_vgp' => $machine->requiresVgp(),
            'last_vgp_at' => $machine->last_vgp_at?->toDateString(),
            'vgp_expires_at' => $machine->vgpExpiresAt()?->toDateString(),
            'vgp_ok_today' => ! $machine->requiresVgp() || $rules->vgp($machine, $today) === [],
            'workshop_periods' => $machine->workshopPeriods
                ->filter(fn (WorkshopPeriod $period) => $period->ends_at->gte($today))
                ->map(fn (WorkshopPeriod $period) => [
                    'id' => $period->id,
                    'starts_at' => $period->starts_at->toDateString(),
                    'ends_at' => $period->ends_at->toDateString(),
                    'reason' => $period->reason,
                    'status' => $period->hasStartedOn($today) ? 'current' : 'planned',
                ])
                ->values()
                ->all(),
        ];
    }
}
