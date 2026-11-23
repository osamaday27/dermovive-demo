<?php

namespace App\Enums;

enum StockMovementType: string
{
    use HasLabel;

    case Opening = 'opening';
    case Sale = 'sale';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
}
