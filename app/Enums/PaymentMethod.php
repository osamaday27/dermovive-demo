<?php

namespace App\Enums;

enum PaymentMethod: string
{
    use HasLabel;

    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case Credit = 'credit';
}
