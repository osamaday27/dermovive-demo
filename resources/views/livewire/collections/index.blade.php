<div class="space-y-5">
    <h1 class="text-xl font-semibold">{{ t('nav.collections') }}</h1>

    <div class="card p-4">
        <div class="font-semibold mb-3">{{ t('coll.new') }}</div>
        <x-alert-errors/>
        <div class="grid sm:grid-cols-3 xl:grid-cols-6 gap-3">
            <x-field :label="t('nav.customers')" name="customer_id" class="sm:col-span-2"><select class="input" wire:model.live="customer_id"><option value="">— {{ t('pos.select_customer') }} —</option>
                @foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->code }} · {{ $c->name }}</option>@endforeach</select></x-field>
            <x-field :label="t('common.date')" name="date"><input type="date" class="input" wire:model="date" max="{{ today()->toDateString() }}"></x-field>
            <x-field :label="t('coll.current_balance')"><div class="input bg-page num font-semibold">{{ $balance !== null ? m($balance) : '—' }}</div></x-field>
            <x-field :label="t('common.amount')" name="amount"><input class="input num" wire:model.live.debounce.300ms="amount" inputmode="decimal"></x-field>
            <x-field :label="t('common.method')" name="method"><select class="input" wire:model.live="method">@foreach ($methods as $mth)<option value="{{ $mth->value }}">{{ $mth->label() }}</option>@endforeach</select></x-field>
            <x-field :label="t('treas.account')" name="treasury_account_id" class="sm:col-span-2"><select class="input" wire:model="treasury_account_id">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></x-field>
            <x-field :label="t('common.notes')" name="notes" class="sm:col-span-4"><input class="input" wire:model="notes"></x-field>
        </div>

        @if ($customer_id !== '')
            <div class="mt-4 flex items-center justify-between"><div class="font-semibold text-sm">{{ t('coll.open_invoices') }}</div>
                <button class="btn btn-sm" wire:click="autoAllocate">{{ t('coll.auto') }}</button></div>
            <div class="overflow-x-auto mt-2"><table class="tbl">
                <thead><tr><th>{{ t('invs.no') }}</th><th>{{ t('common.date') }}</th><th class="n">{{ t('common.total') }}</th><th class="n">{{ t('pos.remaining') }}</th><th class="n w-40">{{ t('coll.allocate') }}</th></tr></thead>
                <tbody>@forelse ($open as $inv)<tr wire:key="o{{ $inv->id }}"><td class="num">{{ $inv->invoice_no }}</td><td class="num">{{ $inv->invoice_date->format('Y-m-d') }}</td>
                    <td class="n num">{{ m($inv->grand_total) }}</td><td class="n num">{{ m($inv->remaining) }}</td>
                    <td><input class="input !py-1 num text-end" wire:model.live.debounce.300ms="alloc.{{ $inv->id }}" inputmode="decimal"></td></tr>
                @empty <tr><td colspan="5" class="text-muted text-center py-4">{{ t('coll.no_open') }}</td></tr> @endforelse</tbody></table></div>
            <div class="mt-2 text-sm num flex flex-wrap gap-x-6 gap-y-1 text-muted">
                <span>{{ t('coll.allocated') }}: <b class="text-ink">{{ m($allocSum) }}</b></span>
                <span>{{ t('coll.on_account') }}: <b class="text-ink">{{ m(max(0, (float) $amount - (float) $allocSum)) }}</b></span></div>
        @endif
        <div class="mt-4 flex justify-end"><button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ t('coll.record') }}</button></div>
    </div>

    <div class="card">
        <div class="p-3 grid sm:grid-cols-4 gap-3 border-b border-line">
            <select class="input" wire:model.live="f_customer"><option value="">{{ t('nav.customers') }}: {{ t('common.all') }}</option>@foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
            <select class="input" wire:model.live="f_method"><option value="">{{ t('common.method') }}: {{ t('common.all') }}</option>@foreach ($methods as $mth)<option value="{{ $mth->value }}">{{ $mth->label() }}</option>@endforeach</select>
            <input type="date" class="input" wire:model.live="f_from"><input type="date" class="input" wire:model.live="f_to">
        </div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>{{ t('coll.receipt_no') }}</th><th>{{ t('common.date') }}</th><th>{{ t('nav.customers') }}</th><th class="n">{{ t('common.amount') }}</th><th>{{ t('common.method') }}</th><th>{{ t('treas.account') }}</th><th>{{ t('common.status') }}</th><th></th></tr></thead>
            <tbody>@forelse ($receipts as $r)<tr wire:key="r{{ $r->id }}"><td class="num font-medium">{{ $r->receipt_no }}</td><td class="num">{{ $r->receipt_date->format('Y-m-d') }}</td><td>{{ $r->customer->name }}</td>
                <td class="n num">{{ m($r->amount) }}</td><td>{{ \App\Enums\CollectionMethod::from($r->method)->label() }}</td><td>{{ $r->account->name }}</td>
                <td><x-badge color="green">{{ t('coll.posted') }}</x-badge></td><td class="text-end"><button class="btn btn-sm" wire:click="show({{ $r->id }})">{{ t('common.view') }}</button></td></tr>
            @empty <tr><td colspan="8" class="text-center text-muted py-8">{{ t('common.no_data') }}</td></tr> @endforelse</tbody></table></div>
        {{ $receipts->links('pagination.simple') }}
    </div>

    @if ($receipt)
        <x-modal :title="t('coll.receipt').' '.$receipt->receipt_no" width="max-w-xl">
            <div class="flex justify-end mb-3 no-print"><button class="btn btn-primary btn-sm" onclick="window.print()"><x-icon name="print" class="w-4 h-4"/> {{ t('common.print') }}</button></div>
            @php $co = \App\Models\Setting::company(); @endphp
            <div class="print-area bg-white p-4 text-sm">
                <div class="flex justify-between border-b border-line pb-3"><div><div class="font-bold text-base">{{ $co['name'] }}</div><div class="text-xs text-muted">{{ $co['address'] }} · {{ $co['phone'] }}</div></div>
                    <div class="text-end"><div class="font-semibold">{{ t('coll.receipt') }}</div><div class="num">{{ $receipt->receipt_no }}</div><div class="num text-muted">{{ $receipt->receipt_date->format('Y-m-d') }}</div></div></div>
                <dl class="grid grid-cols-2 gap-y-1 py-3"><dt class="text-muted">{{ t('nav.customers') }}</dt><dd>{{ $receipt->customer->name }}</dd>
                    <dt class="text-muted">{{ t('common.method') }}</dt><dd>{{ \App\Enums\CollectionMethod::from($receipt->method)->label() }}</dd>
                    <dt class="text-muted">{{ t('treas.account') }}</dt><dd>{{ $receipt->account->name }}</dd>
                    <dt class="text-muted">{{ t('common.amount') }}</dt><dd class="font-bold num">{{ m($receipt->amount) }} {{ t('common.currency') }}</dd></dl>
                <table class="tbl"><thead><tr><th>{{ t('coll.applied_to') }}</th><th class="n">{{ t('common.amount') }}</th></tr></thead>
                    <tbody>@foreach ($receipt->allocations as $a)<tr><td class="num">{{ $a->invoice?->invoice_no ?? t('coll.on_account') }}</td><td class="n num">{{ m($a->amount) }}</td></tr>@endforeach</tbody></table>
                @if ($receipt->notes)<div class="mt-2 text-xs text-muted">{{ $receipt->notes }}</div>@endif
            </div>
        </x-modal>
    @endif
</div>
