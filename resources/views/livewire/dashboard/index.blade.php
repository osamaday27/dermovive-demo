<div class="space-y-6">
    <h1 class="text-xl font-semibold">{{ t('nav.dashboard') }}</h1>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <x-kpi :label="t('dash.today_sales')" :value="m($todaySales['total'])" :hint="$todaySales['count'].' '.t('dash.invoices')"/>
        <x-kpi :label="t('dash.month_sales')" :value="m($monthSales['total'])" :hint="$monthSales['count'].' '.t('dash.invoices')"/>
        <x-kpi :label="t('dash.receivables')" :value="m($receivables['total'])" :hint="$receivables['count'].' '.t('dash.debtors')"/>
        <x-kpi :label="t('dash.treasury_balance')" :value="m($treasury['balance'])"/>
    </div>
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <a href="{{ route('inventory') }}"><x-kpi :label="t('dash.low_stock')" :value="$inventory['low_stock']"/></a>
        <a href="{{ route('inventory') }}"><x-kpi :label="t('dash.near_expiry')" :value="$inventory['near_expiry']"/></a>
        <a href="{{ route('inventory') }}"><x-kpi :label="t('dash.expired')" :value="$inventory['expired']"/></a>
        <x-kpi :label="t('dash.stock_value')" :value="m($inventory['valuation'])"/>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="card p-4 lg:col-span-2">
            <div class="font-semibold mb-3">{{ t('dash.sales_14') }}</div>
            @php $max = max(1, (float) max($daily)); @endphp
            <div class="flex items-end gap-1.5 h-44">
                @foreach ($daily as $day => $val)
                    <div class="flex-1 flex flex-col items-center justify-end h-full" title="{{ $day }}: {{ m($val) }}">
                        <div class="w-full bg-accent rounded-sm" style="height: {{ max(2, ((float) $val / $max) * 100) }}%"></div>
                        <div class="text-[10px] text-muted mt-1 num">{{ substr($day, 8) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card p-4">
            <div class="font-semibold mb-3">{{ t('dash.top_products') }}</div>
            <ul class="space-y-2 text-sm">
                @forelse ($top as $p)
                    <li class="flex justify-between gap-2"><span class="truncate">{{ $p->name }}</span><span class="num text-muted">{{ m($p->revenue) }}</span></li>
                @empty <li class="text-muted">{{ t('common.no_data') }}</li> @endforelse
            </ul>
        </div>
    </div>

    <div class="card overflow-x-auto">
        <div class="px-4 py-3 font-semibold border-b border-line">{{ t('dash.recent') }}</div>
        <table class="tbl">
            <thead><tr><th>{{ t('invs.no') }}</th><th>{{ t('common.date') }}</th><th>{{ t('nav.customers') }}</th><th class="n">{{ t('common.total') }}</th><th>{{ t('common.status') }}</th></tr></thead>
            <tbody>
            @foreach ($recent as $i)
                <tr><td class="num">{{ $i->invoice_no }}</td><td class="num">{{ $i->invoice_date->format('Y-m-d') }}</td><td>{{ $i->customer->name }}</td>
                    <td class="n num">{{ m($i->grand_total) }}</td>
                    <td><x-badge :color="['paid'=>'green','partial'=>'amber','unpaid'=>'red'][$i->status]">{{ \App\Enums\PaymentStatus::from($i->status)->label() }}</x-badge></td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
