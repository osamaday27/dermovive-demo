@php $co = \App\Models\Setting::company(); @endphp
<div x-data="{ layout: 'a4' }">
    <div class="no-print flex flex-wrap items-center justify-between gap-2 mb-4">
        <div class="flex rounded border border-line overflow-hidden text-sm">
            <button type="button" class="px-3 py-1.5" :class="layout==='a4' ? 'bg-accent text-white' : 'bg-white'" @click="layout='a4'">{{ t('print.a4') }}</button>
            <button type="button" class="px-3 py-1.5" :class="layout==='thermal' ? 'bg-accent text-white' : 'bg-white'" @click="layout='thermal'">{{ t('print.thermal') }}</button>
        </div>
        <button type="button" class="btn btn-primary" onclick="window.print()"><x-icon name="print" class="w-4 h-4"/> {{ t('common.print') }}</button>
    </div>

    <div class="print-area">
        {{-- A4 layout --}}
        <div x-show="layout==='a4'" class="bg-white p-6 text-sm">
            <div class="flex justify-between border-b border-line pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 border border-dashed border-line rounded flex items-center justify-center text-[10px] text-muted text-center">{{ t('print.logo') }}</div>
                    <div><div class="text-lg font-bold">{{ $co['name'] }}</div><div class="text-xs text-muted">{{ $co['address'] }}<br>{{ $co['phone'] }} · {{ t('cust.tax_id') }}: <span class="num">{{ $co['tax_id'] }}</span></div></div>
                </div>
                <div class="text-end"><div class="text-lg font-semibold">{{ t('print.invoice') }}</div>
                    <div class="num">{{ $invoice->invoice_no }}</div><div class="num text-muted">{{ $invoice->invoice_date->format('Y-m-d') }}</div></div>
            </div>
            <div class="py-3"><div class="text-xs text-muted">{{ t('print.bill_to') }}</div>
                <div class="font-semibold">{{ $invoice->customer->name }}</div>
                <div class="text-xs text-muted">{{ $invoice->customer->address }} · <span class="num">{{ $invoice->customer->phone }}</span></div></div>
            <table class="tbl">
                <thead><tr><th>#</th><th>{{ t('common.name') }}</th><th>{{ t('inv.batch_no') }}</th><th>{{ t('inv.unit') }}</th><th class="n">{{ t('pos.qty') }}</th><th class="n">{{ t('inv.price') }}</th><th class="n">{{ t('pos.disc') }}</th><th class="n">{{ t('inv.vat') }}</th><th class="n">{{ t('common.total') }}</th></tr></thead>
                <tbody>@foreach ($invoice->items as $i => $it)
                    <tr><td class="num">{{ $i + 1 }}</td><td>{{ $it->product->localized_name }}<div class="text-xs text-muted num">{{ $it->product->sku }}</div></td>
                        <td class="num text-xs">{{ $it->batch->batch_no }}<div class="text-muted">{{ $it->batch->expiry_date->format('Y-m-d') }}</div></td>
                        <td>{{ \App\Enums\ProductUnit::tryFrom($it->unit)?->label() }}</td><td class="n num">{{ q($it->quantity) }}</td><td class="n num">{{ m($it->unit_price) }}</td>
                        <td class="n num">{{ m($it->discount_amount) }}</td><td class="n num">{{ q($it->vat_rate) }}%</td><td class="n num">{{ m($it->line_total) }}</td></tr>
                @endforeach</tbody>
            </table>
            <div class="flex justify-end mt-4"><table class="text-sm w-72">
                @foreach ([['pos.subtotal',$invoice->subtotal],['pos.discount',$invoice->discount_total],['pos.taxable',$invoice->taxable_amount],['inv.vat',$invoice->vat_total]] as [$k,$v])
                    <tr><td class="py-0.5 text-muted">{{ t($k) }}</td><td class="n num">{{ m($v) }}</td></tr>@endforeach
                <tr class="font-bold border-t border-line"><td class="py-1">{{ t('pos.grand_total') }}</td><td class="n num">{{ m($invoice->grand_total) }} {{ t('common.currency') }}</td></tr>
                <tr><td class="py-0.5 text-muted">{{ t('pos.paid') }}</td><td class="n num">{{ m($invoice->paid_amount) }}</td></tr>
                <tr><td class="py-0.5 text-muted">{{ t('pos.remaining') }}</td><td class="n num">{{ m($invoice->remaining) }}</td></tr>
            </table></div>
            <div class="mt-8 text-center text-xs text-muted">{{ t('print.thanks') }}</div>
        </div>

        {{-- Thermal 80mm layout --}}
        <div x-show="layout==='thermal'" x-cloak class="thermal mx-auto bg-white p-2">
            <div class="text-center"><div class="font-bold text-sm">{{ $co['name'] }}</div><div>{{ $co['address'] }}</div><div class="num">{{ $co['phone'] }}</div></div>
            <hr class="my-1 border-dashed">
            <div class="num">{{ $invoice->invoice_no }} · {{ $invoice->invoice_date->format('Y-m-d') }}</div>
            <div>{{ $invoice->customer->name }}</div>
            <hr class="my-1 border-dashed">
            <table>@foreach ($invoice->items as $it)
                <tr><td colspan="2">{{ $it->product->localized_name }}</td></tr>
                <tr><td class="num">{{ q($it->quantity) }} × {{ m($it->unit_price) }}</td><td class="n num">{{ m($it->line_total) }}</td></tr>
            @endforeach</table>
            <hr class="my-1 border-dashed">
            <table>
                <tr><td>{{ t('pos.subtotal') }}</td><td class="n num">{{ m($invoice->subtotal) }}</td></tr>
                <tr><td>{{ t('pos.discount') }}</td><td class="n num">{{ m($invoice->discount_total) }}</td></tr>
                <tr><td>{{ t('inv.vat') }}</td><td class="n num">{{ m($invoice->vat_total) }}</td></tr>
                <tr class="font-bold"><td>{{ t('pos.grand_total') }}</td><td class="n num">{{ m($invoice->grand_total) }}</td></tr>
                <tr><td>{{ t('pos.paid') }}</td><td class="n num">{{ m($invoice->paid_amount) }}</td></tr>
                <tr><td>{{ t('pos.remaining') }}</td><td class="n num">{{ m($invoice->remaining) }}</td></tr>
            </table>
            <div class="text-center mt-2">{{ t('print.thanks') }}</div>
        </div>
    </div>
</div>
