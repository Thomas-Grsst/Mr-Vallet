<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Machine extends Model
{
    public $timestamps = false;

    protected $dateFormat = 'Y-m-d';

    protected $fillable = ['ref', 'type', 'agency_id', 'last_vgp_at'];

    protected function casts(): array
    {
        return [
            'last_vgp_at' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<Agency, $this> */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** @return HasMany<WorkshopPeriod, $this> */
    public function workshopPeriods(): HasMany
    {
        return $this->hasMany(WorkshopPeriod::class)->orderBy('starts_at');
    }

    public function requiresVgp(): bool
    {
        return str_starts_with($this->type, 'Nacelle');
    }

    public function vgpExpiresAt(): ?Carbon
    {
        return $this->last_vgp_at?->copy()->addMonthsNoOverflow(config('vallet.vgp_validity_months'));
    }
}
