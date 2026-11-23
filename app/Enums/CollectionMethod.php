<?php

namespace App\Enums;

enum CollectionMethod: string
{
    use HasLabel;

    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Check = 'check';
}
