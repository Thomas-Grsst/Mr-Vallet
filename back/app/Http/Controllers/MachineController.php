<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchMachinesRequest;
use App\Http\Requests\UpdateVgpRequest;
use App\Http\Requests\UpdateWorkshopRequest;
use App\Models\Machine;
use App\Services\ReservationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class MachineController extends Controller
{
    public function __construct(private ReservationRules $rules) {}

    public function index(SearchMachinesRequest $request): JsonResponse
    {
        $from = $request->filled('from') ? Carbon::parse($request->string('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->string('to')) : null;

        $machines = Machine::query()
            ->with('agency')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->orderBy('type')
            ->orderBy('ref')
            ->get()
            ->map(function (Machine $machine) use ($from, $to) {
                $violations = $from && $to ? $this->rules->check($machine, $from, $to) : null;

                return [
                    ...$this->present($machine),
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

    public function updateWorkshop(UpdateWorkshopRequest $request, Machine $machine): JsonResponse
    {
        $machine->update([
            'workshop_until' => $request->input('until'),
            'workshop_note' => $request->input('until') ? $request->input('note') : null,
        ]);

        return response()->json($this->present($machine->load('agency')));
    }

    public function updateVgp(UpdateVgpRequest $request, Machine $machine): JsonResponse
    {
        $machine->update(['last_vgp_at' => $request->input('last_vgp_at')]);

        return response()->json($this->present($machine->load('agency')));
    }

    /** @return array<string, mixed> */
    private function present(Machine $machine): array
    {
        return [
            'ref' => $machine->ref,
            'type' => $machine->type,
            'agency' => $machine->agency->name,
            'requires_vgp' => $machine->requiresVgp(),
            'last_vgp_at' => $machine->last_vgp_at?->toDateString(),
            'vgp_expires_at' => $machine->vgpExpiresAt()?->toDateString(),
            'vgp_ok_today' => ! $machine->requiresVgp() || $this->rules->vgp($machine, ReservationRules::today()) === [],
            'workshop_until' => $machine->workshop_until?->toDateString(),
            'workshop_note' => $machine->workshop_note,
        ];
    }
}
