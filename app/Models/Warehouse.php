<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['is_default' => 'boolean'];

    public static function default(): self
    {
        return static::where('is_default', true)->first() ?? static::firstOrCreate(['name' => 'Main Warehouse'], ['is_default' => true]);
    }
}
