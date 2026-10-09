<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KeyAccount extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public static function matching(string $client): ?self
    {
        $normalized = Str::lower(trim($client));

        return self::query()->get()->first(fn (self $account) => Str::lower($account->name) === $normalized);
    }
}
