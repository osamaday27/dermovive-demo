<div class="space-y-5">
    <div class="flex items-center justify-between no-print"><h1 class="text-xl font-semibold">{{ t('nav.statements') }}</h1>
        @if ($st)<div class="flex gap-2"><button class="btn" wire:click="exportCsv"><span>{{ t('common.export_csv') }}</span></button>
            <button class="btn btn-primary" onclick="window.print()"><x-icon name="print" class="w-4 h-4"/> {{ t('common.print') }}</button></div>@endif</div>

    <div class="card p-3 grid sm:grid-cols-4 gap-3 no-print">
        <div class="sm:col-span-2"><label class="lbl">{{ t('nav.customers') }}</label>
            <select class="input" wire:model.live="customer"><option value="">— {{ t('pos.select_customer') }} —</option>
                @foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->code }} · {{ $c->name }}</option>@endforeach</select></div>
        <div><label class="lbl">{{ t('common.from') }}</label><input type="date" class="input" wire:model.live="from"></div>
        <div><label class="lbl">{{ t('common.to') }}</label><input type="date" class="input" wire:model.live="to"></div>
        <div class="sm:col-span-2"><label class="lbl">{{ t('common.type') }}</label>
            <select class="input" wire:model.live="type"><option value="">{{ t('common.all') }}</option>@foreach ($types as $tp)<option value="{{ $tp->value }}">{{ $tp->label() }}</option>@endforeach</select></div>
    </div>

    @if ($st && $selected)
        <div class="print-area space-y-4">
            <div class="hidden print:block"><div class="text-lg font-bold">{{ \App\Models\Setting::company()['name'] }}</div>
                <div>{{ t('nav.statements') }} — {{ $selected->name }} ({{ $selected->code }}) · {{ $from ?: '…' }} → {{ $to ?: '…' }}</div></div>
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <x-kpi :label="t('stmt.opening')" :value="m($st['opening'])"/>
                <x-kpi :label="t('stmt.total_debit')" :value="m($st['debit'])"/>
                <x-kpi :label="t('stmt.total_credit')" :value="m($st['credit'])"/>
                <x-kpi :label="t('stmt.closing')" :value="m($st['closing'])" :hint="$from === '' && $to === '' && $type === '' ? (\App\Support\Money::eq($st['closing'], $actual) ? '✓ '.t('stmt.reconciled') : t('stmt.not_reconciled')) : null"/>
            </div>
            <div class="card overflow-x-auto"><table class="tbl">
                <thead><tr><th>{{ t('common.date') }}</th><th>{{ t('stmt.ref') }}</th><th>{{ t('common.type') }}</th><th>{{ t('common.description') }}</th><th class="n">{{ t('stmt.debit') }}</th><th class="n">{{ t('stmt.credit') }}</th><th class="n">{{ t('stmt.running') }}</th></tr></thead>
                <tbody>
                    @if ($from !== '')<tr class="bg-page"><td colspan="6" class="font-medium">{{ t('stmt.opening') }}</td><td class="n num font-medium">{{ m($st['opening']) }}</td></tr>@endif
                    @forelse ($st['rows'] as $r) @php $tx = $r['tx']; @endphp
                        <tr wire:key="tx{{ $tx->id }}"><td class="num">{{ $tx->trx_date->format('Y-m-d') }}</td>
                            <td><button class="num text-accent hover:underline" wire:click="showDetail({{ $tx->id }})">{{ $tx->ref_no }}</button></td>
                            <td>{{ \App\Enums\LedgerType::from($tx->type)->label() }}</td><td>{{ $tx->description }}</td>
                            <td class="n num">{{ (float) $tx->debit ? m($tx->debit) : '' }}</td><td class="n num">{{ (float) $tx->credit ? m($tx->credit) : '' }}</td>
                            <td class="n num font-medium">{{ m($r['running']) }}</td></tr>
                    @empty <tr><td colspan="7" class="text-center text-muted py-8">{{ t('common.no_data') }}</td></tr> @endforelse
                </tbody></table></div>
        </div>
    @else
        <div class="card p-10 text-center text-muted">{{ t('stmt.pick') }}</div>
    @endif

    @if ($detail)
        <x-modal :title="t('stmt.details').' · '.$detail['tx']->ref_no" width="max-w-2xl">
            <div class="text-sm mb-3">{{ \App\Enums\LedgerType::from($detail['tx']->type)->label() }} — {{ $detail['tx']->description }}</div>
            @php $ref = $detail['ref']; @endphp
            @if ($ref instanceof \App\Models\Invoice)
                <table class="tbl"><tbody>@foreach ($ref->items as $it)<tr><td>{{ $it->product->localized_name }}</td><td class="n num">{{ q($it->quantity) }} × {{ m($it->unit_price) }}</td><td class="n num">{{ m($it->line_total) }}</td></tr>@endforeach
                    <tr class="font-semibold"><td colspan="2">{{ t('pos.grand_total') }}</td><td class="n num">{{ m($ref->grand_total) }}</td></tr></tbody></table>
            @elseif ($ref instanceof \App\Models\CollectionReceipt)
                <div class="text-sm mb-2">{{ $ref->account->name }} · <span class="num">{{ m($ref->amount) }}</span></div>
                <table class="tbl"><tbody>@foreach ($ref->allocations as $a)<tr><td class="num">{{ $a->invoice?->invoice_no ?? t('coll.on_account') }}</td><td class="n num">{{ m($a->amount) }}</td></tr>@endforeach</tbody></table>
            @else <div class="text-muted text-sm">{{ t('common.no_data') }}</div> @endif
        </x-modal>
    @endif
</div>
