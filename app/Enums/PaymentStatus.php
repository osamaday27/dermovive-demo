<?php

namespace App\Enums;

enum PaymentStatus: string
{
    use HasLabel;

    case Paid = 'paid';
    case Partial = 'partial';
    case Unpaid = 'unpaid';
}
