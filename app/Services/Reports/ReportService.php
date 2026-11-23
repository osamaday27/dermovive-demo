<?php

namespace App\Services\Reports;

use App\Enums\TreasuryTxType;
use App\Models\CustomerTransaction;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\TreasuryAccount;
use App\Models\TreasuryTransaction;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Treasury\TreasuryService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(private CustomerLedgerService $ledger, private TreasuryService $treasury)
    {
    }

    /** @return array{0:Carbon,1:Carbon} */
    public function range(string $preset, ?string $from = null, ?string $to = null): array
    {
        return match ($preset) {
            'today' => [Carbon::today(), Carbon::today()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::today()],
            'custom' => [Carbon::parse($from ?: today())->startOfDay(), Carbon::parse($to ?: today())->startOfDay()],
            default => [Carbon::now()->startOfMonth(), Carbon::today()],
        };
    }

    private function inv(Carbon $f, Carbon $t)
    {
        return Invoice::whereBetween('invoice_date', [$f->toDateString(), $t->toDateString()]);
    }

    public function sales(Carbon $f, Carbon $t): array
    {
        $r = $this->inv($f, $t)->selectRaw('COUNT(*) as c, COALESCE(SUM(grand_total),0) as total, COALESCE(SUM(taxable_amount),0) as net, COALESCE(SUM(discount_total),0) as disc, COALESCE(SUM(vat_total),0) as vat')->first();
        $count = (int) $r->c;
        return [
            'total' => Money::round($r->total), 'net' => Money::round($r->net), 'discounts' => Money::round($r->disc),
            'vat' => Money::round($r->vat), 'count' => $count,
            'average' => $count ? Money::round(Money::div($r->total, (string) $count)) : '0.00',
        ];
    }

    /** @return array<string,string> date => total */
    public function dailySales(Carbon $f, Carbon $t): array
    {
        $raw = $this->inv($f, $t)->selectRaw('invoice_date as d, SUM(grand_total) as total')->groupBy('invoice_date')->pluck('total', 'd')->all();
        $out = [];
        $byDay = [];
        foreach ($raw as $k => $v) { $byDay[substr((string) $k, 0, 10)] = Money::round($v); }
        for ($d = $f->copy(); $d->lte($t); $d->addDay()) { $out[$d->toDateString()] = $byDay[$d->toDateString()] ?? '0.00'; }
        return $out;
    }

    public function profit(Carbon $f, Carbon $t): array
    {
        $net = $this->sales($f, $t)['net'];
        $cogs = DB::table('invoice_items')->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$f->toDateString(), $t->toDateString()])
            ->selectRaw('COALESCE(SUM(invoice_items.quantity * invoice_items.unit_cost),0) as cogs')->value('cogs');
        $cogs = Money::round($cogs);
        $gross = Money::round(Money::sub($net, $cogs));
        return [
            'net' => $net, 'cogs' => $cogs, 'gross' => $gross,
            'margin' => Money::gt($net, '0') ? Money::round(Money::mul(Money::div($gross, $net), '100')) : '0.00',
        ];
    }

    public function inventory(): array
    {
        $today = Carbon::today()->toDateString();
        $in90 = Carbon::today()->addDays(90)->toDateString();
        $stock = ProductBatch::selectRaw('COALESCE(SUM(available_qty),0) as qty, COALESCE(SUM(available_qty * unit_cost),0) as val')->first();
        return [
            'products' => Product::count(),
            'qty' => $stock->qty,
            'valuation' => Money::round($stock->val),
            'low_stock' => Product::active()->whereRaw(Product::STOCK_SQL.' <= min_stock')->count(),
            'near_expiry' => ProductBatch::where('available_qty', '>', 0)->whereBetween('expiry_date', [$today, $in90])->count(),
            'expired' => ProductBatch::where('available_qty', '>', 0)->where('expiry_date', '<', $today)->count(),
        ];
    }

    public function receivables(): array
    {
        $summary = $this->ledger->receivables();
        $top = Customer::withBalance()->get()->filter(fn ($c) => Money::gt($c->balance, '0'))
            ->sortByDesc(fn ($c) => (float) $c->balance)->take(10)->values();

        $overdue = '0'; $overdueCount = 0;
        $open = Invoice::with('customer')->whereColumn('paid_amount', '<', 'grand_total')->get();
        foreach ($open as $inv) {
            $due = $inv->invoice_date->copy()->addDays((int) ($inv->customer->payment_terms_days ?? 30));
            if ($due->lt(Carbon::today())) { $overdue = Money::add($overdue, $inv->remaining); $overdueCount++; }
        }
        return ['total' => $summary['total'], 'count' => $summary['count'], 'top' => $top, 'overdue' => Money::round($overdue), 'overdue_count' => $overdueCount];
    }

    public function topProducts(Carbon $f, Carbon $t, int $limit = 10)
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$f->toDateString(), $t->toDateString()])
            ->groupBy('products.id', 'products.sku', 'products.name_ar', 'products.name_en', 'products.name_fr')
            ->selectRaw('products.id, products.sku, products.name_ar, products.name_en, products.name_fr,
                SUM(invoice_items.quantity) as units,
                SUM(invoice_items.line_total - invoice_items.vat_amount) as revenue,
                SUM(invoice_items.quantity * invoice_items.unit_cost) as cogs')
            ->orderByDesc('revenue')->limit($limit)->get()
            ->map(function ($r) {
                $r->name = $r->{'name_'.app()->getLocale()} ?: $r->name_en;
                $r->revenue = Money::round($r->revenue);
                $r->profit = Money::round(Money::sub($r->revenue, $r->cogs));
                return $r;
            });
    }

    public function treasury(Carbon $f, Carbon $t): array
    {
        $excluded = [TreasuryTxType::Opening->value, TreasuryTxType::TransferIn->value, TreasuryTxType::TransferOut->value];
        $range = fn () => TreasuryTransaction::whereBetween('tx_date', [$f->toDateString(), $t->toDateString()]);
        $tot = $range()->whereNotIn('type', $excluded)->selectRaw('COALESCE(SUM(inflow),0) as i, COALESCE(SUM(outflow),0) as o')->first();
        $expenses = $range()->where('type', TreasuryTxType::CashOut->value)->sum('outflow');

        $accounts = TreasuryAccount::orderBy('id')->get()->map(function ($a) use ($f, $t) {
            $mv = TreasuryTransaction::where('account_id', $a->id)->whereBetween('tx_date', [$f->toDateString(), $t->toDateString()])
                ->selectRaw('COALESCE(SUM(inflow),0) as i, COALESCE(SUM(outflow),0) as o')->first();
            return ['name' => $a->name, 'balance' => $this->treasury->balance($a), 'in' => Money::round($mv->i), 'out' => Money::round($mv->o)];
        });
        return [
            'inflows' => Money::round($tot->i), 'outflows' => Money::round($tot->o), 'expenses' => Money::round($expenses),
            'accounts' => $accounts, 'balance' => Money::sum($accounts->pluck('balance')),
        ];
    }
}
