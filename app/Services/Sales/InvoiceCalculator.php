<?php

namespace App\Services\Sales;

use App\Support\Money;

class InvoiceCalculator
{
    /**
     * @param array<int,array{qty:mixed,price:mixed,discount:mixed,vat:mixed}> $lines
     * Order of operations per line: gross -> item discount -> invoice discount (pro-rata) -> VAT on net.
     */
    public function calculate(array $lines, $invoiceDiscountPct = '0'): array
    {
        $out = []; $sub = '0'; $disc = '0'; $tax = '0'; $vat = '0';
        foreach ($lines as $k => $l) {
            $gross = Money::round(Money::mul($l['qty'], $l['price']));
            $itemDisc = Money::pct($gross, $l['discount']);
            $net1 = Money::sub($gross, $itemDisc);
            $invDisc = Money::pct($net1, $invoiceDiscountPct);
            $net = Money::round(Money::sub($net1, $invDisc));
            $vatAmt = Money::pct($net, $l['vat']);
            $out[$k] = [
                'gross' => $gross,
                'discount_amount' => Money::round(Money::add($itemDisc, $invDisc)),
                'net' => $net,
                'vat_amount' => $vatAmt,
                'line_total' => Money::round(Money::add($net, $vatAmt)),
            ];
            $sub = Money::add($sub, $gross);
            $disc = Money::add($disc, $out[$k]['discount_amount']);
            $tax = Money::add($tax, $net);
            $vat = Money::add($vat, $vatAmt);
        }
        return [
            'lines' => $out,
            'subtotal' => Money::round($sub),
            'discount_total' => Money::round($disc),
            'taxable' => Money::round($tax),
            'vat_total' => Money::round($vat),
            'grand_total' => Money::round(Money::add($tax, $vat)),
        ];
    }
}
