<?php

namespace App\Enums;

enum LedgerType: string
{
    use HasLabel;

    case Opening = 'opening';
    case Invoice = 'invoice';
    case InvoicePayment = 'invoice_payment';
    case Collection = 'collection';
    case Adjustment = 'adjustment';
}
