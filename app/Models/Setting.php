<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    public static function get(string $name, ?string $default = null): ?string
    {
        return static::where('name', $name)->value('value') ?? $default;
    }

    public static function put(string $name, ?string $value): void
    {
        static::updateOrCreate(['name' => $name], ['value' => $value]);
    }

    /** @return string[] e.g. ['14','15'] */
    public static function vatRates(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', static::get('vat_rates', '14,15')))));
    }

    public static function company(): array
    {
        return [
            'name' => static::get('company_name', 'Dermovive Pharma'),
            'address' => static::get('company_address', ''),
            'phone' => static::get('company_phone', ''),
            'tax_id' => static::get('company_tax_id', ''),
        ];
    }
}
