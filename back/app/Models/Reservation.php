<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    public $timestamps = false;

    protected $dateFormat = 'Y-m-d';

    protected $fillable = ['machine_id', 'client', 'purchase_order', 'starts_at', 'ends_at', 'entered_by_agency_id', 'cancelled_at', 'cancelled_by_agency_id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date:Y-m-d',
            'ends_at' => 'date:Y-m-d',
            'cancelled_at' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<Machine, $this> */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /** @return BelongsTo<Agency, $this> */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'entered_by_agency_id');
    }

    /** @return BelongsTo<Agency, $this> */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'cancelled_by_agency_id');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function cancellableUntil(): Carbon
    {
        return $this->starts_at->copy()->subDays(2);
    }

    public function isCancellableOn(Carbon $today): bool
    {
        return ! $this->isCancelled() && $today->lte($this->cancellableUntil());
    }

    public function statusOn(Carbon $today): ReservationStatus
    {
        return match (true) {
            $this->isCancelled() => ReservationStatus::Cancelled,
            $this->ends_at->lt($today) => ReservationStatus::Finished,
            $this->starts_at->gt($today) => ReservationStatus::Upcoming,
            default => ReservationStatus::Ongoing,
        };
    }
}
