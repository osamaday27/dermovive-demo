<div class="space-y-3">
    <div class="flex items-center justify-between"><h1 class="text-xl font-semibold">{{ t('nav.pos') }}</h1></div>
    <div class="grid lg:grid-cols-5 gap-4">

        {{-- LEFT: catalogue --}}
        <div class="lg:col-span-3 space-y-3">
            <div class="card p-3 space-y-3">
                <input type="search" class="input" autofocus placeholder="{{ t('pos.search_ph') }}"
                       wire:model.live.debounce.250ms="search" wire:keydown.enter="addByCode">
                <div class="flex flex-wrap gap-1.5">
                    <button class="btn btn-sm {{ $category === 'all' ? 'btn-primary' : '' }}" wire:click="$set('category','all')">{{ t('common.all') }}</button>
                    @foreach ($categories as $c)@if ($c->slug !== 'personal_care')
                        <button class="btn btn-sm {{ $category === $c->slug ? 'btn-primary' : '' }}" wire:click="$set('category','{{ $c->slug }}')">{{ $c->localized_name }}</button>
                    @endif @endforeach
                </div>
            </div>
            <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
                @forelse ($products as $p)
                    @php $sellable = (float) $p->sellable; $exp = $p->next_expiry ? \App\Enums\ExpiryStatus::for($p->next_expiry) : null; @endphp
                    <button wire:key="prod{{ $p->id }}" wire:click="addProduct({{ $p->id }})" @disabled($sellable <= 0)
                            class="card p-3 text-start hover:border-accent disabled:opacity-50 disabled:cursor-not-allowed">
                        <div class="h-14 rounded bg-accent-soft mb-2 flex items-center justify-center text-accent text-xs font-semibold">{{ $p->category->localized_name }}</div>
                        <div class="text-sm font-medium leading-snug line-clamp-2 min-h-10">{{ $p->localized_name }}</div>
                        <div class="text-xs text-muted num mt-1">{{ $p->sku }}</div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="font-semibold num">{{ m($p->selling_price) }}</span>
                            <span class="text-xs num {{ $sellable <= (float) $p->min_stock ? 'text-amber-700' : 'text-muted' }}">{{ t('inv.stock') }}: {{ q($sellable) }}</span>
                        </div>
                        @if ($exp)<div class="mt-1"><x-badge :color="$exp->color()">{{ t('pos.exp') }} {{ \Carbon\Carbon::parse($p->next_expiry)->format('Y-m-d') }}</x-badge></div>@endif
                    </button>
                @empty <div class="col-span-full text-center text-muted py-10">{{ t('common.no_data') }}</div> @endforelse
            </div>
        </div>

        {{-- RIGHT: active invoice --}}
        <div class="lg:col-span-2">
            <div class="card p-4 space-y-3 lg:sticky lg:top-20">
                <div class="flex items-center justify-between">
                    <div><div class="text-xs text-muted">{{ t('pos.invoice_no') }}</div><div class="font-semibold num">{{ $nextInvoiceNo }}</div></div>
                    <div class="w-40"><label class="lbl">{{ t('common.date') }}</label><input type="date" class="input" wire:model="invoice_date" max="{{ today()->toDateString() }}"></div>
                </div>
                <div>
                    <label class="lbl">{{ t('pos.customer') }}</label>
                    <select class="input" wire:model.live="customer_id"><option value="">— {{ t('pos.select_customer') }} —</option>
                        @foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->code }} · {{ $c->name }}</option>@endforeach</select>
                    @if ($customer)<div class="text-xs text-muted mt-1 num">{{ t('common.balance') }}: {{ m($customer->balance) }} / {{ t('cust.credit_limit') }}: {{ m($customer->credit_limit) }}</div>@endif
                </div>

                <x-alert-errors/>

                <div class="border border-line rounded max-h-72 overflow-y-auto divide-y divide-line">
                    @forelse ($lines as $i => $l)
                        @php $lp = $lineProducts[$l['product_id']] ?? null; $lt = $totals['lines'][$i] ?? null; @endphp
                        @if ($lp)
                        <div class="p-2 text-sm space-y-1.5" wire:key="line{{ $i }}-{{ $l['product_id'] }}">
                            <div class="flex justify-between gap-2"><div class="font-medium leading-snug">{{ $lp->localized_name }}<span class="text-xs text-muted num"> · {{ $lp->sku }}</span></div>
                                <button class="text-red-600 shrink-0" wire:click="remove({{ $i }})" aria-label="{{ t('common.remove') }}"><x-icon name="x" class="w-4 h-4"/></button></div>
                            <div class="grid grid-cols-3 gap-1.5">
                                <select class="input !py-1 col-span-2 text-xs" wire:model.live="lines.{{ $i }}.batch_id">
                                    <option value="">{{ t('pos.auto_fefo') }}</option>
                                    @foreach ($batches[$l['product_id']] ?? [] as $b)<option value="{{ $b->id }}">{{ $b->batch_no }} · {{ $b->expiry_date->format('Y-m-d') }} · {{ q($b->available_qty) }}</option>@endforeach
                                </select>
                                <select class="input !py-1 text-xs" wire:model.live="lines.{{ $i }}.unit">@foreach (\App\Enums\ProductUnit::cases() as $u)<option value="{{ $u->value }}">{{ $u->label() }}</option>@endforeach</select>
                            </div>
                            <div class="grid grid-cols-4 gap-1.5 items-end">
                                <div class="flex items-center"><button class="btn btn-sm" wire:click="dec({{ $i }})">−</button>
                                    <input class="input !py-1 text-center num mx-1" wire:model.live.debounce.400ms="lines.{{ $i }}.qty" inputmode="decimal">
                                    <button class="btn btn-sm" wire:click="inc({{ $i }})">+</button></div>
                                <div><label class="text-[10px] text-muted">{{ t('pos.disc') }} %</label><input class="input !py-1 num" wire:model.live.debounce.400ms="lines.{{ $i }}.discount" inputmode="decimal"></div>
                                <div><label class="text-[10px] text-muted">{{ t('inv.vat') }} %</label>
                                    <select class="input !py-1" wire:model.live="lines.{{ $i }}.vat">@foreach ($vatRates as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></div>
                                <div class="text-end"><div class="text-[10px] text-muted num">{{ m($lp->selling_price) }}</div><div class="font-semibold num">{{ m($lt['line_total'] ?? 0) }}</div></div>
                            </div>
                        </div>
                        @endif
                    @empty <div class="p-6 text-center text-muted text-sm">{{ t('pos.empty') }}</div> @endforelse
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="lbl">{{ t('pos.invoice_discount') }} %</label><input class="input num" wire:model.live.debounce.400ms="invoice_discount" inputmode="decimal"></div>
                    <div><label class="lbl">{{ t('pos.payment_method') }}</label>
                        <select class="input" wire:model.live="payment_method">@foreach ($methods as $mth)<option value="{{ $mth->value }}">{{ $mth->label() }}</option>@endforeach</select></div>
                    @if ($payment_method !== 'credit')
                        <div><label class="lbl">{{ t('pos.paid') }} <span class="text-muted">({{ t('pos.blank_full') }})</span></label><input class="input num" wire:model.live.debounce.400ms="paid_amount" inputmode="decimal" placeholder="{{ m($totals['grand_total']) }}"></div>
                        <div><label class="lbl">{{ t('treas.account') }}</label>
                            <select class="input" wire:model="treasury_account_id">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
                    @endif
                </div>

                <dl class="text-sm space-y-1 border-t border-line pt-3 num">
                    <div class="flex justify-between"><dt class="text-muted">{{ t('pos.subtotal') }}</dt><dd>{{ m($totals['subtotal']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ t('pos.discount') }}</dt><dd>− {{ m($totals['discount_total']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ t('pos.taxable') }}</dt><dd>{{ m($totals['taxable']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ t('inv.vat') }}</dt><dd>{{ m($totals['vat_total']) }}</dd></div>
                    <div class="flex justify-between text-base font-bold border-t border-line pt-1"><dt>{{ t('pos.grand_total') }}</dt><dd>{{ m($totals['grand_total']) }} {{ t('common.currency') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ t('pos.paid') }}</dt><dd>{{ m($paid) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ t('pos.remaining') }}</dt><dd class="{{ (float) $remaining > 0 ? 'text-red-700 font-semibold' : '' }}">{{ m($remaining) }}</dd></div>
                </dl>

                @if ($exceeds)
                    <div class="rounded border border-amber-200 bg-amber-50 text-amber-800 text-xs p-2">
                        {{ t('pos.over_limit') }}
                        @if ($canOverride)<label class="flex items-center gap-2 mt-1 font-medium"><input type="checkbox" wire:model="credit_override"> {{ t('pos.override') }}</label>@endif
                    </div>
                @endif

                <div class="flex gap-2">
                    <button class="btn" wire:click="clear" wire:confirm="{{ t('common.confirm') }}">{{ t('pos.clear') }}</button>
                    <button class="btn btn-primary flex-1" wire:click="completeSale" wire:loading.attr="disabled" wire:target="completeSale" @disabled(! count($lines))>
                        <span wire:loading.remove wire:target="completeSale">{{ t('pos.complete') }}</span><span wire:loading wire:target="completeSale">…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($printInvoice)
        <x-modal :title="t('print.preview').' · '.$printInvoice->invoice_no" close="closePrint" width="max-w-3xl">
            @include('partials.invoice-print', ['invoice' => $printInvoice])
        </x-modal>
    @endif
</div>
