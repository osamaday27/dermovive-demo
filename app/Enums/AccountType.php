<?php

namespace App\Enums;

enum AccountType: string
{
    use HasLabel;

    case Cash = 'cash';
    case Bank = 'bank';
    case Wallet = 'wallet';
}
