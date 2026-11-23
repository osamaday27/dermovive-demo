<?php

namespace App\Livewire\Reports;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Services\Reports\ReportService;
use App\Support\Csv;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url] public string $tab = 'sales';
    #[Url] public string $preset = 'month';
    #[Url] public string $from = '';
    #[Url] public string $to = '';

    public const TABS = ['sales', 'profit', 'inventory', 'receivables', 'products', 'treasury'];

    public function setTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) { $this->tab = $tab; }
    }

    private function window(): array
    {
        $p = in_array($this->preset, ['today', 'week', 'month', 'custom'], true) ? $this->preset : 'month';
        return app(ReportService::class)->range($p, $this->from ?: null, $this->to ?: null);
    }

    private function lowStock()
    {
        return Product::active()->whereRaw(Product::STOCK_SQL.' <= min_stock')->withSum('batches as stock', 'available_qty')->orderBy('name_en')->limit(15)->get();
    }

    private function nearExpiry()
    {
        return ProductBatch::with('product')->where('available_qty', '>', 0)
            ->where('expiry_date', '<=', today()->addDays(90)->toDateString())->orderBy('expiry_date')->limit(15)->get();
    }

    public function exportCsv()
    {
        [$f, $t] = $this->window();
        $r = app(ReportService::class);
        $name = 'report-'.$this->tab.'-'.today()->toDateString().'.csv';
        switch ($this->tab) {
            case 'sales':
                return Csv::download($name, [t('common.date'), t('rep.total_sales')], collect($r->dailySales($f, $t))->map(fn ($v, $k) => [$k, $v])->values());
            case 'products':
                return Csv::download($name, [t('inv.sku'), t('common.name'), t('rep.units'), t('rep.revenue'), t('rep.gross_profit')],
                    $r->topProducts($f, $t, 50)->map(fn ($p) => [$p->sku, $p->name, $p->units, $p->revenue, $p->profit]));
            case 'receivables':
                return Csv::download($name, [t('common.name'), t('common.balance')], $r->receivables()['top']->map(fn ($c) => [$c->name, $c->balance]));
            case 'treasury':
                $tr = $r->treasury($f, $t);
                return Csv::download($name, [t('treas.account'), t('treas.inflows'), t('treas.outflows'), t('common.balance')],
                    $tr['accounts']->map(fn ($a) => [$a['name'], $a['in'], $a['out'], $a['balance']]));
            case 'inventory':
                return Csv::download($name, [t('inv.sku'), t('common.name'), t('inv.stock'), t('inv.min_stock')],
                    $this->lowStock()->map(fn ($p) => [$p->sku, $p->localized_name, $p->stock, $p->min_stock]));
            default:
                $p = $r->profit($f, $t);
                return Csv::download($name, [t('common.name'), t('common.amount')], [[t('rep.net_sales'), $p['net']], [t('rep.cogs'), $p['cogs']], [t('rep.gross_profit'), $p['gross']], [t('rep.margin').' %', $p['margin']]]);
        }
    }

    public function render()
    {
        [$f, $t] = $this->window();
        $r = app(ReportService::class);
        $d = match ($this->tab) {
            'sales' => ['s' => $r->sales($f, $t), 'daily' => $r->dailySales($f, $t)],
            'profit' => ['p' => $r->profit($f, $t), 's' => $r->sales($f, $t)],
            'inventory' => ['i' => $r->inventory(), 'low' => $this->lowStock(), 'near' => $this->nearExpiry()],
            'receivables' => ['rc' => $r->receivables()],
            'products' => ['top' => $r->topProducts($f, $t, 15)],
            default => ['tr' => $r->treasury($f, $t)],
        };
        return view('livewire.reports.index', ['d' => $d, 'f' => $f, 't' => $t, 'tabs' => self::TABS])->title(t('nav.reports'));
    }
}
