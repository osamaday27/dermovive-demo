<?php

namespace App\Livewire\Customers;

use App\Actions\Customers\SaveCustomerAction;
use App\Enums\CustomerCategory;
use App\Livewire\Concerns\HasFilters;
use App\Models\Customer;
use App\Services\Accounting\CustomerLedgerService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    use HasFilters;

    #[Url] public string $search = '';
    public string $category = '';
    public string $status = '';
    protected array $resetOn = ['search', 'category', 'status'];

    public bool $showForm = false;
    #[Locked] public ?int $editingId = null;
    #[Locked] public ?int $detailId = null;
    public array $form = [];

    private function blank(): array
    {
        return ['name' => '', 'phone' => '', 'email' => '', 'address' => '', 'category' => 'pharmacy', 'tax_id' => '',
            'credit_limit' => '0', 'opening_balance' => '0', 'payment_terms_days' => '30', 'notes' => ''];
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = $this->blank();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->resetValidation();
        $c = Customer::findOrFail($id);
        $this->editingId = $c->id;
        $this->form = [
            'name' => $c->name, 'phone' => (string) $c->phone, 'email' => (string) $c->email, 'address' => (string) $c->address,
            'category' => $c->category, 'tax_id' => (string) $c->tax_id, 'credit_limit' => (string) $c->credit_limit,
            'opening_balance' => (string) $c->opening_balance, 'payment_terms_days' => (string) $c->payment_terms_days, 'notes' => (string) $c->notes,
        ];
        $this->showForm = true;
    }

    public function save(SaveCustomerAction $action): void
    {
        $rules = [
            'form.name' => 'required|string|max:150',
            'form.phone' => 'nullable|string|max:30',
            'form.email' => 'nullable|email|max:150',
            'form.address' => 'nullable|string|max:255',
            'form.category' => ['required', Rule::in(CustomerCategory::values())],
            'form.tax_id' => 'nullable|string|max:30',
            'form.credit_limit' => 'required|numeric|min:0|max:100000000',
            'form.payment_terms_days' => 'required|integer|min:0|max:365',
            'form.notes' => 'nullable|string|max:1000',
        ];
        if (! $this->editingId) { $rules['form.opening_balance'] = 'required|numeric|min:-100000000|max:100000000'; }
        $this->validate($rules);

        $action->execute($this->form, $this->editingId);
        $this->notify(t('cust.saved'));
        $this->closeModal();
    }

    public function toggleArchive(int $id): void
    {
        $c = Customer::findOrFail($id);
        $c->update(['status' => $c->status === 'active' ? 'archived' : 'active']);
        $this->notify($c->status === 'archived' ? t('cust.archived_msg') : t('cust.restored_msg'));
    }

    public function show(int $id): void { $this->detailId = Customer::findOrFail($id)->id; }

    public function closeModal(): void
    {
        $this->showForm = false;
        $this->detailId = null;
    }

    public function render()
    {
        $customers = Customer::withBalance()
            ->when($this->search !== '', fn ($q) => $q->where(function ($w) {
                $like = self::like($this->search);
                $w->where('name', 'like', $like)->orWhere('code', 'like', $like)->orWhere('phone', 'like', $like);
            }))
            ->when($this->category !== '', fn ($q) => $q->where('category', $this->category))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderBy('name')->paginate(10);

        $detail = null;
        if ($this->detailId) {
            $detail = Customer::withBalance()->findOrFail($this->detailId);
            $detail->setRelation('invoices', $detail->invoices()->orderByDesc('invoice_date')->orderByDesc('id')->limit(8)->get());
            $detail->setRelation('receipts', $detail->receipts()->with('account')->orderByDesc('receipt_date')->orderByDesc('id')->limit(8)->get());
        }

        $rec = app(CustomerLedgerService::class)->receivables();
        return view('livewire.customers.index', [
            'customers' => $customers, 'detail' => $detail,
            'stats' => ['total' => Customer::count(), 'active' => Customer::where('status', 'active')->count(), 'receivables' => $rec['total'], 'debtors' => $rec['count']],
        ])->title(t('nav.customers'));
    }
}
