<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    public function products(): HasMany { return $this->hasMany(Product::class); }

    public function getLocalizedNameAttribute(): string
    {
        return $this->{'name_'.app()->getLocale()} ?: $this->name_en;
    }
}
