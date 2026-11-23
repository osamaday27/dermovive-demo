<?php

namespace App\Livewire\Dashboard;

use App\Models\Invoice;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Reports\ReportService;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $r = app(ReportService::class);
        $today = today();
        $month = now()->startOfMonth();

        return view('livewire.dashboard.index', [
            'todaySales' => $r->sales($today, $today),
            'monthSales' => $r->sales($month, $today),
            'receivables' => app(CustomerLedgerService::class)->receivables(),
            'treasury' => $r->treasury($month, $today),
            'inventory' => $r->inventory(),
            'daily' => $r->dailySales($today->copy()->subDays(13), $today),
            'recent' => Invoice::with('customer')->orderByDesc('invoice_date')->orderByDesc('id')->limit(6)->get(),
            'top' => $r->topProducts($today->copy()->subDays(29), $today, 5),
        ])->title(t('nav.dashboard'));
    }
}
