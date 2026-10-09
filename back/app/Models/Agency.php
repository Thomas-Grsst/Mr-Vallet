<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    /** @return HasMany<Machine, $this> */
    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }
}
