<?php

namespace App\Enums;

enum TreasuryTxType: string
{
    use HasLabel;

    case Opening = 'opening';
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case SalePayment = 'sale_payment';
    case Collection = 'collection';
    case Reversal = 'reversal';
}
