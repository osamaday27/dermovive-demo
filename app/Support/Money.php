<?php

namespace App\Support;

/** BCMath wrapper: money is always a numeric STRING, never a float. */
class Money
{
    public static function num($v): string
    {
        $v = is_string($v) ? trim(str_replace(',', '', $v)) : (string) $v;
        return is_numeric($v) ? $v : '0';
    }
    public static function add($a, $b): string { return bcadd(self::num($a), self::num($b), 6); }
    public static function sub($a, $b): string { return bcsub(self::num($a), self::num($b), 6); }
    public static function mul($a, $b): string { return bcmul(self::num($a), self::num($b), 6); }
    public static function div($a, $b): string { return bcdiv(self::num($a), self::num($b), 6); }
    public static function cmp($a, $b): int { return bccomp(self::num($a), self::num($b), 4); }
    public static function gt($a, $b): bool { return self::cmp($a, $b) > 0; }
    public static function gte($a, $b): bool { return self::cmp($a, $b) >= 0; }
    public static function lt($a, $b): bool { return self::cmp($a, $b) < 0; }
    public static function eq($a, $b): bool { return self::cmp($a, $b) === 0; }

    /** Half-up rounding to $p decimals. */
    public static function round($v, int $p = 2): string
    {
        $v = self::num($v);
        $neg = str_starts_with($v, '-');
        $abs = ltrim($v, '-+');
        $r = bcadd($abs, '0.'.str_repeat('0', $p).'5', $p);
        return ($neg && bccomp($r, '0', $p) !== 0) ? '-'.$r : $r;
    }

    /** amount * pct / 100, rounded to 2 decimals. */
    public static function pct($amount, $pct): string
    {
        return self::round(bcdiv(bcmul(self::num($amount), self::num($pct), 6), '100', 6));
    }

    public static function sum(iterable $values): string
    {
        $t = '0';
        foreach ($values as $v) { $t = self::add($t, $v); }
        return self::round($t);
    }
}
