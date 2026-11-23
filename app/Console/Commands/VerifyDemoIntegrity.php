<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\TreasuryAccount;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Treasury\TreasuryService;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyDemoIntegrity extends Command
{
    protected $signature = 'demo:verify';
    protected $description = 'Reconcile customer ledgers, invoices, treasury and stock against their source records';

    public function handle(CustomerLedgerService $ledger, TreasuryService $treasury): int
    {
        $errors = 0;
        $fail = function (string $m) use (&$errors) { $this->error('✗ '.$m); $errors++; };

        // 1) Invoice paid_amount == payments at sale + collection allocations
        foreach (Invoice::withCount([])->get() as $inv) {
            $p = DB::table('invoice_payments')->where('invoice_id', $inv->id)->sum('amount');
            $a = DB::table('collection_allocations')->where('invoice_id', $inv->id)->sum('amount');
            if (! Money::eq(Money::add($p, $a), $inv->paid_amount)) { $fail("Invoice {$inv->invoice_no}: paid_amount mismatch"); }
            if ($inv->status !== Invoice::statusFor($inv->grand_total, $inv->paid_amount)->value) { $fail("Invoice {$inv->invoice_no}: status mismatch"); }
        }
        $this->info('Invoices checked: '.Invoice::count());

        // 2) Customer ledger balance == opening + invoices - payments - receipts
        foreach (Customer::all() as $c) {
            $expected = Money::sub(Money::sub(Money::add($c->opening_balance, DB::table('invoices')->where('customer_id', $c->id)->sum('grand_total')),
                DB::table('invoice_payments')->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')->where('invoices.customer_id', $c->id)->sum('invoice_payments.amount')),
                DB::table('collection_receipts')->where('customer_id', $c->id)->sum('amount'));
            if (! Money::eq($expected, $ledger->balance($c))) { $fail("Customer {$c->code}: ledger {$ledger->balance($c)} != expected ".Money::round($expected)); }
        }
        $this->info('Customers checked: '.Customer::count());

        // 3) Treasury: balance == opening + inflows - outflows  (every row traceable)
        foreach (TreasuryAccount::all() as $a) {
            $in = DB::table('treasury_transactions')->where('account_id', $a->id)->sum('inflow');
            $out = DB::table('treasury_transactions')->where('account_id', $a->id)->sum('outflow');
            if (! Money::eq(Money::sub($in, $out), $treasury->balance($a))) { $fail("Treasury {$a->name}: balance mismatch"); }
        }
        $sales = DB::table('invoice_payments')->sum('amount') + DB::table('collection_receipts')->sum('amount');
        $sysIn = DB::table('treasury_transactions')->whereIn('type', ['sale_payment', 'collection'])->sum('inflow');
        if (! Money::eq($sales, $sysIn)) { $fail('Treasury system inflows do not equal payments + receipts'); }
        $this->info('Treasury accounts checked: '.TreasuryAccount::count());

        // 4) Stock: batch available_qty == SUM(qty_in - qty_out) of its movements
        foreach (ProductBatch::all() as $b) {
            $net = StockMovement::where('batch_id', $b->id)->selectRaw('COALESCE(SUM(qty_in - qty_out),0) as n')->value('n');
            if (! Money::eq($net, $b->available_qty)) { $fail("Batch {$b->batch_no}: movements {$net} != available {$b->available_qty}"); }
            if (Money::lt($b->available_qty, '0')) { $fail("Batch {$b->batch_no}: negative stock"); }
        }
        $this->info('Batches checked: '.ProductBatch::count());

        $errors ? $this->error("Integrity check FAILED ($errors issues)") : $this->info('✔ All balances reconcile.');
        return $errors ? self::FAILURE : self::SUCCESS;
    }
}
