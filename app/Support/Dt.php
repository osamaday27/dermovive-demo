<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * DATE columns are written as plain 'Y-m-d' strings and read back as Carbon.
 * (Avoids Eloquent's 'date' cast rewriting them to 'Y-m-d H:i:s', which breaks string comparisons on SQLite.)
 */
class Dt
{
    public static function attr(): Attribute
    {
        return Attribute::get(fn ($v) => $v ? Carbon::parse($v)->startOfDay() : null);
    }
}
