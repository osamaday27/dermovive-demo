<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">{{ t('nav.customers') }}</h1>
        <button class="btn btn-primary" wire:click="create">+ {{ t('cust.new') }}</button>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <x-kpi :label="t('cust.total')" :value="$stats['total']"/>
        <x-kpi :label="t('cust.active')" :value="$stats['active']"/>
        <x-kpi :label="t('dash.receivables')" :value="m($stats['receivables'])"/>
        <x-kpi :label="t('cust.with_balance')" :value="$stats['debtors']"/>
    </div>

    <div class="card">
        <div class="p-3 grid sm:grid-cols-4 gap-3 border-b border-line">
            <input type="search" class="input sm:col-span-2" placeholder="{{ t('cust.search_ph') }}" wire:model.live.debounce.300ms="search">
            <select class="input" wire:model.live="category"><option value="">{{ t('common.all') }} — {{ t('cust.category') }}</option>
                @foreach (\App\Enums\CustomerCategory::cases() as $c)<option value="{{ $c->value }}">{{ $c->label() }}</option>@endforeach</select>
            <select class="input" wire:model.live="status"><option value="">{{ t('common.all') }} — {{ t('common.status') }}</option>
                <option value="active">{{ t('common.active') }}</option><option value="archived">{{ t('common.archived') }}</option></select>
        </div>
        <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>{{ t('common.code') }}</th><th>{{ t('common.name') }}</th><th>{{ t('cust.category') }}</th><th>{{ t('common.phone') }}</th>
                <th class="n">{{ t('cust.credit_limit') }}</th><th class="n">{{ t('common.balance') }}</th><th>{{ t('common.status') }}</th><th></th></tr></thead>
            <tbody>
            @forelse ($customers as $c)
                <tr wire:key="c{{ $c->id }}">
                    <td class="num">{{ $c->code }}</td>
                    <td><button class="font-medium text-accent hover:underline text-start" wire:click="show({{ $c->id }})">{{ $c->name }}</button></td>
                    <td>{{ \App\Enums\CustomerCategory::from($c->category)->label() }}</td>
                    <td class="num">{{ $c->phone }}</td>
                    <td class="n num">{{ m($c->credit_limit) }}</td>
                    <td class="n num {{ (float) $c->balance > 0 ? 'text-red-700 font-medium' : '' }}">{{ m($c->balance) }}</td>
                    <td><x-badge :color="$c->status === 'active' ? 'green' : 'gray'">{{ t('common.'.$c->status) }}</x-badge></td>
                    <td class="whitespace-nowrap text-end">
                        <a class="btn btn-sm" href="{{ route('statements', ['customer' => $c->id]) }}">{{ t('nav.statements') }}</a>
                        <button class="btn btn-sm" wire:click="edit({{ $c->id }})">{{ t('common.edit') }}</button>
                        <button class="btn btn-sm" wire:click="toggleArchive({{ $c->id }})" wire:confirm="{{ t('common.confirm') }}">{{ $c->status === 'active' ? t('common.archive') : t('common.restore') }}</button>
                    </td>
                </tr>
            @empty <tr><td colspan="8" class="text-center text-muted py-8">{{ t('common.no_data') }}</td></tr> @endforelse
            </tbody>
        </table></div>
        {{ $customers->links('pagination.simple') }}
    </div>

    @if ($showForm)
        <x-modal :title="$editingId ? t('cust.edit') : t('cust.new')">
            <form wire:submit="save" class="grid sm:grid-cols-2 gap-4">
                <x-field :label="t('common.name')" name="form.name" class="sm:col-span-2"><input class="input" wire:model="form.name"></x-field>
                <x-field :label="t('common.phone')" name="form.phone"><input class="input" wire:model="form.phone"></x-field>
                <x-field :label="t('common.email')" name="form.email"><input class="input" wire:model="form.email"></x-field>
                <x-field :label="t('common.address')" name="form.address" class="sm:col-span-2"><input class="input" wire:model="form.address"></x-field>
                <x-field :label="t('cust.category')" name="form.category"><select class="input" wire:model="form.category">
                    @foreach (\App\Enums\CustomerCategory::cases() as $c)<option value="{{ $c->value }}">{{ $c->label() }}</option>@endforeach</select></x-field>
                <x-field :label="t('cust.tax_id')" name="form.tax_id"><input class="input" wire:model="form.tax_id"></x-field>
                <x-field :label="t('cust.credit_limit')" name="form.credit_limit"><input class="input num" wire:model="form.credit_limit" inputmode="decimal"></x-field>
                <x-field :label="t('cust.opening_balance')" name="form.opening_balance"><input class="input num" wire:model="form.opening_balance" inputmode="decimal" @disabled($editingId)></x-field>
                <x-field :label="t('cust.terms')" name="form.payment_terms_days"><input class="input num" wire:model="form.payment_terms_days" inputmode="numeric"></x-field>
                <x-field :label="t('common.notes')" name="form.notes" class="sm:col-span-2"><textarea class="input" rows="2" wire:model="form.notes"></textarea></x-field>
                <div class="sm:col-span-2 flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="closeModal">{{ t('common.cancel') }}</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">{{ t('common.save') }}</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($detail)
        <x-modal :title="$detail->name" width="max-w-4xl">
            <div class="grid sm:grid-cols-4 gap-3 mb-4">
                <x-kpi :label="t('common.code')" :value="$detail->code"/>
                <x-kpi :label="t('cust.credit_limit')" :value="m($detail->credit_limit)"/>
                <x-kpi :label="t('common.balance')" :value="m($detail->balance)"/>
                <x-kpi :label="t('cust.terms')" :value="$detail->payment_terms_days.' '.t('common.days')"/>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div><div class="font-semibold text-sm mb-2">{{ t('cust.invoices') }}</div>
                    <table class="tbl"><tbody>
                    @forelse ($detail->invoices as $i)
                        <tr><td class="num">{{ $i->invoice_no }}</td><td class="num">{{ $i->invoice_date->format('Y-m-d') }}</td><td class="n num">{{ m($i->grand_total) }}</td>
                        <td><x-badge :color="['paid'=>'green','partial'=>'amber','unpaid'=>'red'][$i->status]">{{ \App\Enums\PaymentStatus::from($i->status)->label() }}</x-badge></td></tr>
                    @empty <tr><td class="text-muted">{{ t('common.no_data') }}</td></tr> @endforelse
                    </tbody></table></div>
                <div><div class="font-semibold text-sm mb-2">{{ t('cust.collections') }}</div>
                    <table class="tbl"><tbody>
                    @forelse ($detail->receipts as $r)
                        <tr><td class="num">{{ $r->receipt_no }}</td><td class="num">{{ $r->receipt_date->format('Y-m-d') }}</td><td class="n num">{{ m($r->amount) }}</td><td>{{ $r->account->name }}</td></tr>
                    @empty <tr><td class="text-muted">{{ t('common.no_data') }}</td></tr> @endforelse
                    </tbody></table></div>
            </div>
            <div class="mt-4 flex justify-end"><a class="btn btn-primary" href="{{ route('statements', ['customer' => $detail->id]) }}">{{ t('cust.open_statement') }}</a></div>
        </x-modal>
    @endif
</div>
