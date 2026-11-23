<?php

namespace App\Enums;

trait HasLabel
{
    public function label(): string
    {
        return t('enums.'.class_basename(static::class).'.'.$this->value);
    }

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
