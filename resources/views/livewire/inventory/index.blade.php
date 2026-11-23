<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">{{ t('nav.inventory') }}</h1>
        <button class="btn btn-primary" wire:click="create">+ {{ t('inv.new') }}</button>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-5 gap-4">
        <x-kpi :label="t('inv.total_products')" :value="$summary['products']"/>
        <x-kpi :label="t('inv.stock_value')" :value="m($summary['valuation'])"/>
        <button class="text-start" wire:click="$set('stock','low')"><x-kpi :label="t('dash.low_stock')" :value="$summary['low_stock']"/></button>
        <button class="text-start" wire:click="$set('expiry','near')"><x-kpi :label="t('dash.near_expiry')" :value="$summary['near_expiry']"/></button>
        <button class="text-start" wire:click="$set('expiry','expired')"><x-kpi :label="t('dash.expired')" :value="$summary['expired']"/></button>
    </div>

    <div class="card">
        <div class="p-3 grid sm:grid-cols-5 gap-3 border-b border-line">
            <input type="search" class="input sm:col-span-2" placeholder="{{ t('inv.search_ph') }}" wire:model.live.debounce.300ms="search">
            <select class="input" wire:model.live="category"><option value="">{{ t('inv.category') }}: {{ t('common.all') }}</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->localized_name }}</option>@endforeach</select>
            <select class="input" wire:model.live="expiry"><option value="">{{ t('inv.expiry') }}: {{ t('common.all') }}</option>
                @foreach (['expired','critical','warning','near','valid'] as $e)<option value="{{ $e }}">{{ t('inv.exp_'.$e) }}</option>@endforeach</select>
            <select class="input" wire:model.live="stock"><option value="">{{ t('inv.stock') }}: {{ t('common.all') }}</option>
                @foreach (['out','low','ok'] as $e)<option value="{{ $e }}">{{ t('inv.stk_'.$e) }}</option>@endforeach</select>
        </div>
        <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>{{ t('inv.sku') }}</th><th>{{ t('common.name') }}</th><th>{{ t('inv.category') }}</th><th class="n">{{ t('inv.price') }}</th>
                <th class="n">{{ t('inv.stock') }}</th><th class="n">{{ t('inv.min_stock') }}</th><th>{{ t('inv.expiry') }}</th><th></th></tr></thead>
            <tbody>
            @forelse ($products as $p)
                @php $w = $p->worstExpiry(); $low = (float) $p->stock <= (float) $p->min_stock; @endphp
                <tr wire:key="p{{ $p->id }}">
                    <td class="num">{{ $p->sku }}</td>
                    <td><button class="font-medium text-accent hover:underline text-start" wire:click="show({{ $p->id }})">{{ $p->localized_name }}</button>
                        <div class="text-xs text-muted num">{{ $p->barcode }}</div></td>
                    <td>{{ $p->category->localized_name }}</td>
                    <td class="n num">{{ m($p->selling_price) }}</td>
                    <td class="n num">{{ q($p->stock) }} @if ($low)<x-badge :color="(float) $p->stock <= 0 ? 'red' : 'amber'">{{ t('inv.low') }}</x-badge>@endif</td>
                    <td class="n num">{{ q($p->min_stock) }}</td>
                    <td>@if ($w)<x-badge :color="$w->color()">{{ $w->label() }}</x-badge>@else<span class="text-muted">—</span>@endif</td>
                    <td class="whitespace-nowrap text-end">
                        <button class="btn btn-sm" wire:click="label({{ $p->id }})">{{ t('inv.label') }}</button>
                        <button class="btn btn-sm" wire:click="edit({{ $p->id }})">{{ t('common.edit') }}</button>
                    </td>
                </tr>
            @empty <tr><td colspan="8" class="text-center text-muted py-8">{{ t('common.no_data') }}</td></tr> @endforelse
            </tbody>
        </table></div>
        {{ $products->links('pagination.simple') }}
    </div>

    {{-- Product form --}}
    @if ($showForm)
        <x-modal :title="$editingId ? t('inv.edit') : t('inv.new')" width="max-w-3xl">
            <form wire:submit="save" class="grid sm:grid-cols-3 gap-4">
                <x-field :label="t('inv.sku')" name="form.sku"><input class="input num" wire:model="form.sku"></x-field>
                <x-field :label="t('inv.barcode')" name="form.barcode"><input class="input num" wire:model="form.barcode"></x-field>
                <x-field :label="t('inv.brand')" name="form.brand"><input class="input" wire:model="form.brand"></x-field>
                <x-field :label="t('inv.name_ar')" name="form.name_ar"><input class="input" dir="rtl" wire:model="form.name_ar"></x-field>
                <x-field :label="t('inv.name_en')" name="form.name_en"><input class="input" wire:model="form.name_en"></x-field>
                <x-field :label="t('inv.name_fr')" name="form.name_fr"><input class="input" wire:model="form.name_fr"></x-field>
                <x-field :label="t('inv.category')" name="form.category_id"><select class="input" wire:model="form.category_id">@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->localized_name }}</option>@endforeach</select></x-field>
                <x-field :label="t('inv.unit')" name="form.unit"><select class="input" wire:model="form.unit">@foreach (\App\Enums\ProductUnit::cases() as $u)<option value="{{ $u->value }}">{{ $u->label() }}</option>@endforeach</select></x-field>
                <x-field :label="t('common.status')" name="form.status"><select class="input" wire:model="form.status"><option value="active">{{ t('common.active') }}</option><option value="archived">{{ t('common.archived') }}</option></select></x-field>
                <x-field :label="t('inv.purchase_price')" name="form.purchase_price"><input class="input num" wire:model="form.purchase_price" inputmode="decimal"></x-field>
                <x-field :label="t('inv.price')" name="form.selling_price"><input class="input num" wire:model="form.selling_price" inputmode="decimal"></x-field>
                <x-field :label="t('inv.vat')" name="form.vat_rate"><select class="input" wire:model="form.vat_rate">@foreach ($vatRates as $v)<option value="{{ $v }}">{{ $v }}%</option>@endforeach</select></x-field>
                <x-field :label="t('inv.min_stock')" name="form.min_stock"><input class="input num" wire:model="form.min_stock" inputmode="decimal"></x-field>
                <x-field :label="t('common.notes')" name="form.description" class="sm:col-span-2"><input class="input" wire:model="form.description"></x-field>
                <div class="sm:col-span-3 flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="closeModal">{{ t('common.cancel') }}</button>
                    <button class="btn btn-primary">{{ t('common.save') }}</button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- Product details: batches + movements --}}
    @if ($detail)
        <x-modal :title="$detail->localized_name" width="max-w-5xl">
            <div class="grid sm:grid-cols-4 gap-3 mb-4">
                <x-kpi :label="t('inv.sku')" :value="$detail->sku"/>
                <x-kpi :label="t('inv.price')" :value="m($detail->selling_price)"/>
                <x-kpi :label="t('inv.stock')" :value="q($detail->batches->sum('available_qty'))"/>
                <x-kpi :label="t('inv.vat')" :value="q($detail->vat_rate).'%'"/>
            </div>
            <div class="flex items-center justify-between mb-2"><div class="font-semibold text-sm">{{ t('inv.batches') }}</div>
                <button class="btn btn-sm btn-primary" wire:click="newBatch">+ {{ t('inv.add_batch') }}</button></div>
            <div class="overflow-x-auto mb-5"><table class="tbl">
                <thead><tr><th>{{ t('inv.batch_no') }}</th><th>{{ t('inv.mfg') }}</th><th>{{ t('inv.exp_date') }}</th><th>{{ t('inv.expiry') }}</th><th class="n">{{ t('inv.unit_cost') }}</th><th class="n">{{ t('inv.initial') }}</th><th class="n">{{ t('inv.available') }}</th><th></th></tr></thead>
                <tbody>@foreach ($detail->batches as $b) @php $s = $b->expiryStatus(); @endphp
                    <tr><td class="num">{{ $b->batch_no }}</td><td class="num">{{ $b->mfg_date?->format('Y-m-d') }}</td><td class="num">{{ $b->expiry_date->format('Y-m-d') }}</td>
                        <td><x-badge :color="$s->color()" class="{{ $s === \App\Enums\ExpiryStatus::Expired ? 'font-bold' : '' }}">{{ $s->label() }}</x-badge></td>
                        <td class="n num">{{ m($b->unit_cost) }}</td><td class="n num">{{ q($b->initial_qty) }}</td><td class="n num">{{ q($b->available_qty) }}</td>
                        <td class="text-end"><button class="btn btn-sm" wire:click="adjust({{ $b->id }})">{{ t('inv.adjust') }}</button></td></tr>
                @endforeach</tbody></table></div>

            <div class="font-semibold text-sm mb-2">{{ t('inv.movements') }}</div>
            <div class="overflow-x-auto"><table class="tbl">
                <thead><tr><th>{{ t('common.date') }}</th><th>{{ t('inv.batch_no') }}</th><th>{{ t('common.type') }}</th><th>{{ t('stmt.ref') }}</th><th class="n">{{ t('inv.qty_in') }}</th><th class="n">{{ t('inv.qty_out') }}</th><th class="n">{{ t('inv.balance_after') }}</th><th>{{ t('common.notes') }}</th></tr></thead>
                <tbody>@foreach ($detail->movements as $mv)
                    <tr><td class="num">{{ $mv->moved_at->format('Y-m-d') }}</td><td class="num">{{ $mv->batch->batch_no }}</td><td>{{ \App\Enums\StockMovementType::from($mv->type)->label() }}</td><td class="num">{{ $mv->ref_no }}</td>
                        <td class="n num">{{ q($mv->qty_in) }}</td><td class="n num">{{ q($mv->qty_out) }}</td><td class="n num">{{ q($mv->balance_after) }}</td><td>{{ $mv->note }}</td></tr>
                @endforeach</tbody></table></div>
        </x-modal>

        @if ($showBatch)
            <x-modal :title="t('inv.add_batch')" close="closeSub" width="max-w-lg">
                <form wire:submit="saveBatch" class="grid grid-cols-2 gap-4">
                    <x-field :label="t('inv.batch_no')" name="batchForm.batch_no" class="col-span-2"><input class="input num" wire:model="batchForm.batch_no"></x-field>
                    <x-field :label="t('inv.mfg')" name="batchForm.mfg_date"><input type="date" class="input" wire:model="batchForm.mfg_date"></x-field>
                    <x-field :label="t('inv.exp_date')" name="batchForm.expiry_date"><input type="date" class="input" wire:model="batchForm.expiry_date"></x-field>
                    <x-field :label="t('inv.unit_cost')" name="batchForm.unit_cost"><input class="input num" wire:model="batchForm.unit_cost" inputmode="decimal"></x-field>
                    <x-field :label="t('inv.qty')" name="batchForm.qty"><input class="input num" wire:model="batchForm.qty" inputmode="decimal"></x-field>
                    <div class="col-span-2 flex justify-end gap-2"><button type="button" class="btn" wire:click="closeSub">{{ t('common.cancel') }}</button><button class="btn btn-primary">{{ t('common.save') }}</button></div>
                </form>
            </x-modal>
        @endif
        @if ($showAdjust)
            <x-modal :title="t('inv.adjust')" close="closeSub" width="max-w-md">
                <form wire:submit="saveAdjust" class="grid gap-4">
                    <x-field :label="t('inv.direction')" name="adjustForm.direction"><select class="input" wire:model="adjustForm.direction"><option value="in">{{ t('inv.adj_in') }}</option><option value="out">{{ t('inv.adj_out') }}</option></select></x-field>
                    <x-field :label="t('inv.qty')" name="adjustForm.qty"><input class="input num" wire:model="adjustForm.qty" inputmode="decimal"></x-field>
                    <x-field :label="t('inv.reason')" name="adjustForm.note"><input class="input" wire:model="adjustForm.note"></x-field>
                    <div class="flex justify-end gap-2"><button type="button" class="btn" wire:click="closeSub">{{ t('common.cancel') }}</button><button class="btn btn-primary">{{ t('common.save') }}</button></div>
                </form>
            </x-modal>
        @endif
    @endif

    {{-- Barcode label preview --}}
    @if ($label)
        <x-modal :title="t('inv.label')" width="max-w-sm">
            <div class="print-area mx-auto border border-line rounded p-3 text-center w-64">
                <div class="text-sm font-semibold">{{ $label->localized_name }}</div>
                <div class="text-xs text-muted num">{{ $label->sku }}</div>
                <div class="flex justify-center my-2">{!! $barcodeSvg !!}</div>
                <div class="text-xs num tracking-widest">{{ $label->barcode }}</div>
                <div class="text-base font-bold num mt-1">{{ m($label->selling_price) }} {{ t('common.currency') }}</div>
            </div>
            <div class="mt-4 flex justify-end gap-2 no-print"><button class="btn" wire:click="closeModal">{{ t('common.close') }}</button>
                <button class="btn btn-primary" onclick="window.print()"><x-icon name="print" class="w-4 h-4"/> {{ t('common.print') }}</button></div>
        </x-modal>
    @endif
</div>
