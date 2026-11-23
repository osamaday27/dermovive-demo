<?php

namespace App\Models;

/** Posted financial rows can never be edited or deleted – corrections are new (reversal) rows. */
trait Immutable
{
    protected static function bootImmutable(): void
    {
        static::updating(fn () => throw new \LogicException('Posted financial records are immutable.'));
        static::deleting(fn () => throw new \LogicException('Posted financial records cannot be deleted.'));
    }
}
