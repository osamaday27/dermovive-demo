<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-2"><h1 class="text-xl font-semibold">{{ t('nav.treasury') }}</h1>
        <div class="flex gap-2"><button class="btn" wire:click="open('in')">+ {{ t('treas.cash_in') }}</button><button class="btn" wire:click="open('out')">− {{ t('treas.cash_out') }}</button>
            <button class="btn btn-primary" wire:click="open('transfer')">⇄ {{ t('treas.transfer') }}</button></div></div>

    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach ($accounts as $a)
            <div class="card p-4" wire:key="acc{{ $a->id }}">
                <div class="flex items-center justify-between"><div class="font-semibold">{{ $a->name }}</div><x-badge color="blue">{{ \App\Enums\AccountType::from($a->type)->label() }}</x-badge></div>
                <div class="text-2xl font-semibold num my-2">{{ m($a->balance) }}</div>
                <div class="flex justify-between text-xs text-muted num"><span>▲ {{ m($a->in) }}</span><span>▼ {{ m($a->out) }}</span></div>
            </div>
        @endforeach
    </div>
    <div class="text-sm text-muted">{{ t('treas.total_balance') }}: <b class="text-ink num">{{ m($total) }} {{ t('common.currency') }}</b></div>

    <div class="card">
        <div class="p-3 grid sm:grid-cols-4 gap-3 border-b border-line">
            <select class="input" wire:model.live="account"><option value="">{{ t('treas.account') }}: {{ t('common.all') }}</option>@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
            <select class="input" wire:model.live="type"><option value="">{{ t('common.type') }}: {{ t('common.all') }}</option>@foreach ($types as $tp)<option value="{{ $tp->value }}">{{ $tp->label() }}</option>@endforeach</select>
            <input type="date" class="input" wire:model.live="from"><input type="date" class="input" wire:model.live="to">
        </div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>{{ t('treas.tx_no') }}</th><th>{{ t('common.date') }}</th><th>{{ t('common.type') }}</th><th>{{ t('treas.account') }}</th><th>{{ t('common.description') }}</th><th>{{ t('stmt.ref') }}</th><th class="n">{{ t('treas.inflow') }}</th><th class="n">{{ t('treas.outflow') }}</th><th class="n">{{ t('stmt.running') }}</th><th></th></tr></thead>
            <tbody>@forelse ($txs as $x)
                <tr wire:key="t{{ $x->id }}" class="{{ in_array($x->id, $reversed) ? 'opacity-50' : '' }}">
                    <td class="num">{{ $x->tx_no }}</td><td class="num">{{ $x->tx_date->format('Y-m-d') }}</td><td>{{ \App\Enums\TreasuryTxType::from($x->type)->label() }}</td><td>{{ $x->account->name }}</td>
                    <td>{{ $x->description }}</td><td class="num">{{ $x->reference }}</td>
                    <td class="n num text-green-700">{{ (float) $x->inflow ? m($x->inflow) : '' }}</td><td class="n num text-red-700">{{ (float) $x->outflow ? m($x->outflow) : '' }}</td>
                    <td class="n num font-medium">{{ m($x->running) }}</td>
                    <td class="text-end">@if (in_array($x->type, $manual) && ! in_array($x->id, $reversed))<button class="btn btn-sm btn-danger" wire:click="reverse({{ $x->id }})" wire:confirm="{{ t('treas.confirm_reverse') }}">{{ t('treas.reverse') }}</button>
                        @elseif (in_array($x->id, $reversed))<x-badge color="gray">{{ t('treas.reversed_badge') }}</x-badge>@endif</td></tr>
            @empty <tr><td colspan="10" class="text-center text-muted py-8">{{ t('common.no_data') }}</td></tr> @endforelse</tbody></table></div>
        {{ $txs->links('pagination.simple') }}
    </div>

    @if ($modal)
        <x-modal :title="t('treas.'.($modal === 'in' ? 'cash_in' : ($modal === 'out' ? 'cash_out' : 'transfer')))" width="max-w-lg">
            <x-alert-errors/>
            <form wire:submit="save" class="grid grid-cols-2 gap-4">
                <x-field :label="$modal === 'transfer' ? t('treas.from_account') : t('treas.account')" name="form.account_id"><select class="input" wire:model="form.account_id">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></x-field>
                @if ($modal === 'transfer')<x-field :label="t('treas.to_account')" name="form.to_account_id"><select class="input" wire:model="form.to_account_id">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></x-field>
                @else <x-field :label="t('stmt.ref')" name="form.reference"><input class="input" wire:model="form.reference"></x-field>@endif
                <x-field :label="t('common.amount')" name="form.amount"><input class="input num" wire:model="form.amount" inputmode="decimal"></x-field>
                <x-field :label="t('common.date')" name="form.date"><input type="date" class="input" wire:model="form.date" max="{{ today()->toDateString() }}"></x-field>
                <x-field :label="t('common.description')" name="form.description" class="col-span-2"><input class="input" wire:model="form.description"></x-field>
                <div class="col-span-2 flex justify-end gap-2"><button type="button" class="btn" wire:click="closeModal">{{ t('common.cancel') }}</button><button class="btn btn-primary" wire:loading.attr="disabled">{{ t('common.save') }}</button></div>
            </form>
        </x-modal>
    @endif
</div>
