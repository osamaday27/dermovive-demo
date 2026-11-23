<div class="space-y-5">
    <h1 class="text-xl font-semibold">{{ t('nav.invoices') }}</h1>
    <div class="card">
        <div class="p-3 grid sm:grid-cols-5 gap-3 border-b border-line">
            <input type="search" class="input" placeholder="{{ t('invs.search_ph') }}" wire:model.live.debounce.300ms="search">
            <select class="input" wire:model.live="status"><option value="">{{ t('common.status') }}: {{ t('common.all') }}</option>@foreach ($statuses as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach</select>
            <select class="input" wire:model.live="method"><option value="">{{ t('common.method') }}: {{ t('common.all') }}</option>@foreach ($methods as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach</select>
            <input type="date" class="input" wire:model.live="from" title="{{ t('common.from') }}">
            <input type="date" class="input" wire:model.live="to" title="{{ t('common.to') }}">
        </div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>{{ t('invs.no') }}</th><th>{{ t('common.date') }}</th><th>{{ t('nav.customers') }}</th><th class="n">{{ t('common.total') }}</th><th class="n">{{ t('pos.paid') }}</th><th class="n">{{ t('pos.remaining') }}</th><th>{{ t('common.method') }}</th><th>{{ t('common.status') }}</th><th></th></tr></thead>
            <tbody>
            @forelse ($invoices as $i)
                <tr wire:key="i{{ $i->id }}"><td class="num font-medium">{{ $i->invoice_no }}</td><td class="num">{{ $i->invoice_date->format('Y-m-d') }}</td><td>{{ $i->customer->name }}</td>
                    <td class="n num">{{ m($i->grand_total) }}</td><td class="n num">{{ m($i->paid_amount) }}</td><td class="n num">{{ m($i->remaining) }}</td>
                    <td>{{ \App\Enums\PaymentMethod::from($i->payment_method)->label() }}</td>
                    <td><x-badge :color="['paid'=>'green','partial'=>'amber','unpaid'=>'red'][$i->status]">{{ \App\Enums\PaymentStatus::from($i->status)->label() }}</x-badge></td>
                    <td class="text-end"><button class="btn btn-sm" wire:click="show({{ $i->id }})">{{ t('common.view') }}</button></td></tr>
            @empty <tr><td colspan="9" class="text-center text-muted py-8">{{ t('common.no_data') }}</td></tr> @endforelse
            </tbody></table></div>
        {{ $invoices->links('pagination.simple') }}
    </div>

    @if ($invoice)
        <x-modal :title="t('invs.invoice').' '.$invoice->invoice_no" width="max-w-4xl">
            @if ($printMode)
                <button class="btn btn-sm mb-3 no-print" wire:click="$set('printMode', false)">‹ {{ t('common.back') }}</button>
                @include('partials.invoice-print', ['invoice' => $invoice])
            @else
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div><div class="font-semibold">{{ $invoice->customer->name }}</div>
                        <a class="text-xs text-accent hover:underline" href="{{ route('customers') }}?search={{ urlencode($invoice->customer->code) }}">{{ $invoice->customer->code }} · {{ t('invs.customer_details') }}</a></div>
                    <div class="flex gap-2"><a class="btn btn-sm" href="{{ route('statements', ['customer' => $invoice->customer_id]) }}">{{ t('nav.statements') }}</a>
                        <button class="btn btn-sm btn-primary" wire:click="$set('printMode', true)"><x-icon name="print" class="w-4 h-4"/> {{ t('common.print') }}</button></div>
                </div>
                <div class="overflow-x-auto"><table class="tbl">
                    <thead><tr><th>{{ t('common.name') }}</th><th>{{ t('inv.batch_no') }}</th><th class="n">{{ t('pos.qty') }}</th><th class="n">{{ t('inv.price') }}</th><th class="n">{{ t('pos.disc') }}</th><th class="n">{{ t('inv.vat') }}</th><th class="n">{{ t('common.total') }}</th></tr></thead>
                    <tbody>@foreach ($invoice->items as $it)
                        <tr><td>{{ $it->product->localized_name }}</td><td class="num text-xs">{{ $it->batch->batch_no }}</td><td class="n num">{{ q($it->quantity) }}</td><td class="n num">{{ m($it->unit_price) }}</td>
                            <td class="n num">{{ m($it->discount_amount) }}</td><td class="n num">{{ m($it->vat_amount) }}</td><td class="n num">{{ m($it->line_total) }}</td></tr>@endforeach</tbody></table></div>
                <div class="grid sm:grid-cols-2 gap-6 mt-5">
                    <div><div class="font-semibold text-sm mb-2">{{ t('invs.payment_history') }}</div>
                        <table class="tbl"><tbody>
                        @foreach ($invoice->payments as $p)<tr><td class="num">{{ $p->paid_at->format('Y-m-d') }}</td><td>{{ \App\Enums\PaymentMethod::from($p->method)->label() }} · {{ $p->account?->name }}</td><td class="n num">{{ m($p->amount) }}</td></tr>@endforeach
                        @foreach ($invoice->allocations as $a)<tr><td class="num">{{ $a->receipt->receipt_date->format('Y-m-d') }}</td><td class="num">{{ $a->receipt->receipt_no }} · {{ $a->receipt->account->name }}</td><td class="n num">{{ m($a->amount) }}</td></tr>@endforeach
                        @if ($invoice->payments->isEmpty() && $invoice->allocations->isEmpty())<tr><td class="text-muted">{{ t('common.no_data') }}</td></tr>@endif
                        </tbody></table></div>
                    <dl class="text-sm space-y-1 num self-start">
                        <div class="flex justify-between"><dt class="text-muted">{{ t('pos.subtotal') }}</dt><dd>{{ m($invoice->subtotal) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">{{ t('pos.discount') }}</dt><dd>{{ m($invoice->discount_total) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">{{ t('inv.vat') }}</dt><dd>{{ m($invoice->vat_total) }}</dd></div>
                        <div class="flex justify-between font-bold border-t border-line pt-1"><dt>{{ t('pos.grand_total') }}</dt><dd>{{ m($invoice->grand_total) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">{{ t('pos.paid') }}</dt><dd>{{ m($invoice->paid_amount) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">{{ t('pos.remaining') }}</dt><dd>{{ m($invoice->remaining) }}</dd></div>
                    </dl>
                </div>
            @endif
        </x-modal>
    @endif
</div>
