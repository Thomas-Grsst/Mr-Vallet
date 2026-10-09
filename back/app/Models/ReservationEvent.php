<?php

namespace App\Models;

use App\Enums\ReservationEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationEvent extends Model
{
    public $timestamps = false;

    protected $dateFormat = 'Y-m-d';

    protected $fillable = [
        'reservation_id', 'type', 'occurred_on', 'user_id',
        'previous_starts_at', 'previous_ends_at', 'new_starts_at', 'new_ends_at',
        'previous_purchase_order', 'new_purchase_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReservationEventType::class,
            'occurred_on' => 'date:Y-m-d',
            'previous_starts_at' => 'date:Y-m-d',
            'previous_ends_at' => 'date:Y-m-d',
            'new_starts_at' => 'date:Y-m-d',
            'new_ends_at' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function author(): string
    {
        $user = $this->user;
        $parts = [$user->name, $user->role->label()];

        if ($user->agency && $user->agency->name !== $user->name) {
            $parts[] = $user->agency->name;
        }

        return implode(' · ', $parts);
    }

    public function describe(): string
    {
        $date = $this->occurred_on->format('d/m/Y');

        if ($this->type === ReservationEventType::Cancelled) {
            return "Annulée le {$date} par {$this->author()}";
        }

        $changes = [];

        if (! $this->previous_starts_at->equalTo($this->new_starts_at) || ! $this->previous_ends_at->equalTo($this->new_ends_at)) {
            $changes[] = "du {$this->previous_starts_at->format('d/m')} au {$this->previous_ends_at->format('d/m')} → du {$this->new_starts_at->format('d/m')} au {$this->new_ends_at->format('d/m')}";
        }

        if ($this->previous_purchase_order !== $this->new_purchase_order) {
            $changes[] = 'bon de commande '.($this->previous_purchase_order ?? 'aucun').' → '.($this->new_purchase_order ?? 'aucun');
        }

        $detail = $changes === [] ? '' : ' : '.implode(', ', $changes);

        return "Modifiée le {$date} par {$this->author()}{$detail}";
    }
}
