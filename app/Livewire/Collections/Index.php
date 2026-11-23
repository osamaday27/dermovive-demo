<?php

namespace App\Livewire\Collections;

use App\Actions\Collections\RecordCollectionAction;
use App\Enums\CollectionMethod;
use App\Exceptions\BusinessException;
use App\Livewire\Concerns\HasFilters;
use App\Models\CollectionReceipt;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\TreasuryAccount;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Sales\CollectionAllocator;
use App\Support\Money;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    use HasFilters;

    public string $customer_id = '';
    public string $date = '';
    public string $amount = '';
    public string $method = 'cash';
    public string $treasury_account_id = '';
    public string $notes = '';
    public array $alloc = [];
    public string $token = '';

    public string $f_customer = '';
    public string $f_from = '';
    public string $f_to = '';
    public string $f_method = '';
    protected array $resetOn = ['f_customer', 'f_from', 'f_to', 'f_method'];

    #[Locked] public ?int $viewId = null;

    public function mount(): void
    {
        $this->date = today()->toDateString();
        $this->token = (string) Str::uuid();
        $this->treasury_account_id = (string) $this->defaultAccount('cash');
    }

    private function defaultAccount(string $method): ?int
    {
        return TreasuryAccount::where('type', $method === 'cash' ? 'cash' : 'bank')->where('is_active', true)->orderBy('id')->value('id');
    }

    public function updatedCustomerId(): void { $this->alloc = []; $this->amount = ''; $this->resetErrorBag(); }
    public function updatedMethod(string $v): void { $this->treasury_account_id = (string) $this->defaultAccount($v); }

    public function autoAllocate(): void
    {
        if ($this->customer_id === '') { return; }
        $amount = $this->amount !== '' ? $this->amount : app(CustomerLedgerService::class)->balance((int) $this->customer_id);
        if ($this->amount === '') { $this->amount = (string) $amount; }
        $this->alloc = array_map('strval', app(CollectionAllocator::class)->suggest((int) $this->customer_id, $amount, null));
    }

    public function save(RecordCollectionAction $action): void
    {
        $this->resetErrorBag();
        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|gt:0',
            'method' => ['required', \Illuminate\Validation\Rule::in(CollectionMethod::values())],
            'treasury_account_id' => 'required|exists:treasury_accounts,id',
            'notes' => 'nullable|string|max:500',
        ]);
        try {
            $r = $action->execute([
                'customer_id' => (int) $this->customer_id, 'date' => $this->date, 'amount' => $this->amount, 'method' => $this->method,
                'treasury_account_id' => (int) $this->treasury_account_id,
                'allocations' => collect($this->alloc)->filter(fn ($v) => $v !== '' && $v !== null)->all(),
                'notes' => $this->notes ?: null, 'idempotency_key' => $this->token,
            ]);
        } catch (BusinessException $e) {
            $this->addError('business', $e->getMessage());
            return;
        }
        $this->dispatch('notify', message: t('coll.saved', ['no' => $r->receipt_no]), type: 'success');
        $this->viewId = $r->id;
        $this->customer_id = ''; $this->amount = ''; $this->alloc = []; $this->notes = ''; $this->token = (string) Str::uuid();
    }

    public function show(int $id): void { $this->viewId = CollectionReceipt::findOrFail($id)->id; }
    public function closeModal(): void { $this->viewId = null; }

    public function render()
    {
        $open = $this->customer_id !== ''
            ? Invoice::where('customer_id', (int) $this->customer_id)->whereColumn('paid_amount', '<', 'grand_total')->orderBy('invoice_date')->orderBy('id')->get()
            : collect();
        $allocSum = Money::sum(collect($this->alloc)->map(fn ($v) => Money::num($v)));

        $receipts = CollectionReceipt::with(['customer', 'account'])
            ->when($this->f_customer !== '', fn ($q) => $q->where('customer_id', (int) $this->f_customer))
            ->when($this->f_method !== '', fn ($q) => $q->where('method', $this->f_method))
            ->when($this->f_from !== '', fn ($q) => $q->where('receipt_date', '>=', $this->f_from))
            ->when($this->f_to !== '', fn ($q) => $q->where('receipt_date', '<=', $this->f_to))
            ->orderByDesc('receipt_date')->orderByDesc('id')->paginate(8);

        return view('livewire.collections.index', [
            'customers' => Customer::orderBy('name')->get(['id', 'code', 'name']),
            'balance' => $this->customer_id !== '' ? app(CustomerLedgerService::class)->balance((int) $this->customer_id) : null,
            'open' => $open, 'allocSum' => $allocSum,
            'accounts' => TreasuryAccount::where('is_active', true)->orderBy('id')->get(),
            'receipts' => $receipts, 'methods' => CollectionMethod::cases(),
            'receipt' => $this->viewId ? CollectionReceipt::with(['customer', 'account', 'allocations.invoice'])->find($this->viewId) : null,
        ])->title(t('nav.collections'));
    }
}
