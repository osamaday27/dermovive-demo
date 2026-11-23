<?php

namespace App\Livewire\Invoices;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Concerns\HasFilters;
use App\Models\Invoice;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    use HasFilters;

    public string $search = '';
    public string $status = '';
    public string $method = '';
    public string $from = '';
    public string $to = '';
    protected array $resetOn = ['search', 'status', 'method', 'from', 'to'];

    #[Locked] public ?int $viewId = null;
    public bool $printMode = false;

    public function show(int $id): void { $this->viewId = Invoice::findOrFail($id)->id; $this->printMode = false; }
    public function closeModal(): void { $this->viewId = null; $this->printMode = false; }

    public function render()
    {
        $invoices = Invoice::with('customer')
            ->when($this->search !== '', fn ($q) => $q->where(function ($w) {
                $like = self::like($this->search);
                $w->where('invoice_no', 'like', $like)->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('code', 'like', $like));
            }))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->method !== '', fn ($q) => $q->where('payment_method', $this->method))
            ->when($this->from !== '', fn ($q) => $q->where('invoice_date', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->where('invoice_date', '<=', $this->to))
            ->orderByDesc('invoice_date')->orderByDesc('id')->paginate(12);

        $invoice = $this->viewId
            ? Invoice::with(['customer', 'items.product', 'items.batch', 'payments.account', 'allocations.receipt.account'])->findOrFail($this->viewId)
            : null;

        return view('livewire.invoices.index', [
            'invoices' => $invoices, 'invoice' => $invoice,
            'statuses' => PaymentStatus::cases(), 'methods' => PaymentMethod::cases(),
        ])->title(t('nav.invoices'));
    }
}
