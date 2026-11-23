<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-2"><h1 class="text-xl font-semibold">{{ t('nav.reports') }}</h1>
        <button class="btn" wire:click="exportCsv">{{ t('common.export_csv') }}</button></div>

    <div class="flex flex-wrap gap-1.5">
        @foreach ($tabs as $tb)<button class="btn btn-sm {{ $tab === $tb ? 'btn-primary' : '' }}" wire:click="setTab('{{ $tb }}')">{{ t('rep.tab_'.$tb) }}</button>@endforeach
    </div>

    @if (in_array($tab, ['sales','profit','products','treasury']))
    <div class="card p-3 flex flex-wrap items-end gap-3">
        <div class="flex rounded border border-line overflow-hidden text-sm">
            @foreach (['today','week','month','custom'] as $p)<button class="px-3 py-1.5 {{ $preset === $p ? 'bg-accent text-white' : 'bg-white' }}" wire:click="$set('preset','{{ $p }}')">{{ t('rep.p_'.$p) }}</button>@endforeach</div>
        @if ($preset === 'custom')<input type="date" class="input w-40" wire:model.live="from"><input type="date" class="input w-40" wire:model.live="to">@endif
        <div class="text-xs text-muted num">{{ $f->toDateString() }} → {{ $t->toDateString() }}</div>
    </div>
    @endif

    @if ($tab === 'sales') @php $s = $d['s']; $max = max(1, (float) max($d['daily'] ?: [0])); @endphp
        <div class="grid grid-cols-2 xl:grid-cols-6 gap-4">
            <x-kpi :label="t('rep.total_sales')" :value="m($s['total'])"/><x-kpi :label="t('rep.net_sales')" :value="m($s['net'])"/>
            <x-kpi :label="t('rep.discounts')" :value="m($s['discounts'])"/><x-kpi :label="t('rep.vat')" :value="m($s['vat'])"/>
            <x-kpi :label="t('rep.invoices')" :value="$s['count']"/><x-kpi :label="t('rep.average')" :value="m($s['average'])"/>
        </div>
        <div class="card p-4"><div class="flex items-end gap-1 h-48 overflow-x-auto">
            @foreach ($d['daily'] as $day => $val)<div class="flex-1 min-w-3 h-full flex flex-col justify-end items-center" title="{{ $day }}: {{ m($val) }}">
                <div class="w-full bg-accent rounded-sm" style="height: {{ max(1, ((float) $val / $max) * 100) }}%"></div></div>@endforeach</div></div>
    @elseif ($tab === 'profit') @php $p = $d['p']; @endphp
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <x-kpi :label="t('rep.net_sales')" :value="m($p['net'])"/><x-kpi :label="t('rep.cogs')" :value="m($p['cogs'])"/>
            <x-kpi :label="t('rep.gross_profit')" :value="m($p['gross'])"/><x-kpi :label="t('rep.margin')" :value="m($p['margin']).' %'"/>
        </div>
        <p class="text-xs text-muted">{{ t('rep.cogs_note') }}</p>
    @elseif ($tab === 'inventory') @php $i = $d['i']; @endphp
        <div class="grid grid-cols-2 xl:grid-cols-6 gap-4">
            <x-kpi :label="t('inv.total_products')" :value="$i['products']"/><x-kpi :label="t('rep.total_qty')" :value="q($i['qty'])"/><x-kpi :label="t('inv.stock_value')" :value="m($i['valuation'])"/>
            <x-kpi :label="t('dash.low_stock')" :value="$i['low_stock']"/><x-kpi :label="t('dash.near_expiry')" :value="$i['near_expiry']"/><x-kpi :label="t('dash.expired')" :value="$i['expired']"/>
        </div>
        <div class="grid lg:grid-cols-2 gap-4">
            <div class="card overflow-x-auto"><div class="px-4 py-3 font-semibold border-b border-line">{{ t('dash.low_stock') }}</div><table class="tbl"><tbody>
                @foreach ($d['low'] as $p)<tr><td class="num">{{ $p->sku }}</td><td>{{ $p->localized_name }}</td><td class="n num">{{ q($p->stock) }} / {{ q($p->min_stock) }}</td></tr>@endforeach</tbody></table></div>
            <div class="card overflow-x-auto"><div class="px-4 py-3 font-semibold border-b border-line">{{ t('dash.near_expiry') }}</div><table class="tbl"><tbody>
                @foreach ($d['near'] as $b) @php $es = $b->expiryStatus(); @endphp<tr><td>{{ $b->product->localized_name }}</td><td class="num">{{ $b->batch_no }}</td><td class="n num">{{ q($b->available_qty) }}</td><td><x-badge :color="$es->color()">{{ $b->expiry_date->format('Y-m-d') }}</x-badge></td></tr>@endforeach</tbody></table></div>
        </div>
    @elseif ($tab === 'receivables') @php $r = $d['rc']; @endphp
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <x-kpi :label="t('rep.outstanding')" :value="m($r['total'])"/><x-kpi :label="t('dash.debtors')" :value="$r['count']"/>
            <x-kpi :label="t('rep.overdue')" :value="m($r['overdue'])" :hint="$r['overdue_count'].' '.t('dash.invoices')"/>
        </div>
        <div class="card overflow-x-auto"><div class="px-4 py-3 font-semibold border-b border-line">{{ t('rep.highest') }}</div><table class="tbl">
            <thead><tr><th>{{ t('common.code') }}</th><th>{{ t('common.name') }}</th><th class="n">{{ t('cust.credit_limit') }}</th><th class="n">{{ t('common.balance') }}</th></tr></thead>
            <tbody>@foreach ($r['top'] as $c)<tr><td class="num">{{ $c->code }}</td><td>{{ $c->name }}</td><td class="n num">{{ m($c->credit_limit) }}</td><td class="n num text-red-700 font-medium">{{ m($c->balance) }}</td></tr>@endforeach</tbody></table></div>
    @elseif ($tab === 'products')
        <div class="card overflow-x-auto"><table class="tbl">
            <thead><tr><th>{{ t('inv.sku') }}</th><th>{{ t('common.name') }}</th><th class="n">{{ t('rep.units') }}</th><th class="n">{{ t('rep.revenue') }}</th><th class="n">{{ t('rep.gross_profit') }}</th></tr></thead>
            <tbody>@forelse ($d['top'] as $p)<tr><td class="num">{{ $p->sku }}</td><td>{{ $p->name }}</td><td class="n num">{{ q($p->units) }}</td><td class="n num">{{ m($p->revenue) }}</td><td class="n num">{{ m($p->profit) }}</td></tr>
            @empty <tr><td colspan="5" class="text-center text-muted py-8">{{ t('common.no_data') }}</td></tr>@endforelse</tbody></table></div>
    @else @php $tr = $d['tr']; @endphp
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <x-kpi :label="t('treas.inflows')" :value="m($tr['inflows'])"/><x-kpi :label="t('treas.outflows')" :value="m($tr['outflows'])"/>
            <x-kpi :label="t('rep.expenses')" :value="m($tr['expenses'])"/><x-kpi :label="t('treas.total_balance')" :value="m($tr['balance'])"/>
        </div>
        <div class="card overflow-x-auto"><table class="tbl">
            <thead><tr><th>{{ t('treas.account') }}</th><th class="n">{{ t('treas.inflow') }}</th><th class="n">{{ t('treas.outflow') }}</th><th class="n">{{ t('common.balance') }}</th></tr></thead>
            <tbody>@foreach ($tr['accounts'] as $a)<tr><td>{{ $a['name'] }}</td><td class="n num">{{ m($a['in']) }}</td><td class="n num">{{ m($a['out']) }}</td><td class="n num font-medium">{{ m($a['balance']) }}</td></tr>@endforeach</tbody></table></div>
    @endif
</div>
