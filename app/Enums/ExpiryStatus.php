<?php

namespace App\Enums;

use Carbon\Carbon;

enum ExpiryStatus: string
{
    use HasLabel;

    case Expired = 'expired';
    case Critical = 'critical';
    case Warning = 'warning';
    case Valid = 'valid';

    public static function for($date): self
    {
        $d = Carbon::parse($date)->startOfDay();
        $today = Carbon::today();
        if ($d->lt($today)) { return self::Expired; }
        $days = $today->diffInDays($d);
        return match (true) {
            $days <= 30 => self::Critical,
            $days <= 90 => self::Warning,
            default => self::Valid,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Expired, self::Critical => 'red', self::Warning => 'amber', self::Valid => 'green',
        };
    }

    public function severity(): int
    {
        return match ($this) { self::Expired => 4, self::Critical => 3, self::Warning => 2, self::Valid => 1 };
    }
}
