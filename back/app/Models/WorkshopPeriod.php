<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WorkshopPeriod extends Model
{
    public $timestamps = false;

    protected $dateFormat = 'Y-m-d';

    protected $fillable = ['machine_id', 'starts_at', 'ends_at', 'reason'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date:Y-m-d',
            'ends_at' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<Machine, $this> */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function overlaps(Carbon $from, Carbon $to): bool
    {
        return $this->starts_at->lte($to) && $this->ends_at->gte($from);
    }

    public function hasStartedOn(Carbon $today): bool
    {
        return $this->starts_at->lte($today);
    }

    public function describe(): string
    {
        $reason = $this->reason ? " ({$this->reason})" : '';

        return "du {$this->starts_at->format('d/m/Y')} au {$this->ends_at->format('d/m/Y')}{$reason}";
    }
}
