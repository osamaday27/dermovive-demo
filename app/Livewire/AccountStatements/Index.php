<?php

namespace App\Livewire\AccountStatements;

use App\Enums\LedgerType;
use App\Models\Customer;
use App\Models\CollectionReceipt;
use App\Models\CustomerTransaction;
use App\Models\Invoice;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Accounting\StatementService;
use App\Support\Csv;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url] public string $customer = '';
    #[Url] public string $from = '';
    #[Url] public string $to = '';
    #[Url] public string $type = '';
    #[Locked] public ?int $detailTxId = null;

    public function showDetail(int $id): void
    {
        $this->detailTxId = CustomerTransaction::where('customer_id', (int) $this->customer)->findOrFail($id)->id;
    }

    public function closeModal(): void { $this->detailTxId = null; }

    private function statement(): ?array
    {
        if ($this->customer === '') { return null; }
        return app(StatementService::class)->build((int) $this->customer, $this->from ?: null, $this->to ?: null, $this->type ?: null);
    }

    public function exportCsv()
    {
        $st = $this->statement();
        abort_unless($st, 404);
        $c = Customer::findOrFail((int) $this->customer);
        $rows = [[$this->from ?: '—', '', t('stmt.opening'), '', '', '', $st['opening']]];
        foreach ($st['rows'] as $r) {
            $tx = $r['tx'];
            $rows[] = [$tx->trx_date->toDateString(), $tx->ref_no, LedgerType::from($tx->type)->label(), $tx->description, $tx->debit, $tx->credit, $r['running']];
        }
        return Csv::download('statement-'.$c->code.'.csv',
            [t('common.date'), t('stmt.ref'), t('common.type'), t('common.description'), t('stmt.debit'), t('stmt.credit'), t('stmt.running')], $rows);
    }

    public function render()
    {
        $st = $this->statement();
        $detail = null;
        if ($this->detailTxId) {
            $tx = CustomerTransaction::findOrFail($this->detailTxId);
            $ref = null;
            if ($tx->reference_type === Invoice::class) { $ref = Invoice::with(['items.product', 'payments'])->find($tx->reference_id); }
            elseif ($tx->reference_type === CollectionReceipt::class) { $ref = CollectionReceipt::with(['allocations.invoice', 'account'])->find($tx->reference_id); }
            $detail = ['tx' => $tx, 'ref' => $ref];
        }
        return view('livewire.account-statements.index', [
            'customers' => Customer::orderBy('name')->get(['id', 'code', 'name']),
            'selected' => $this->customer !== '' ? Customer::find((int) $this->customer) : null,
            'st' => $st, 'detail' => $detail, 'types' => LedgerType::cases(),
            'actual' => $this->customer !== '' ? app(CustomerLedgerService::class)->balance((int) $this->customer) : null,
        ])->title(t('nav.statements'));
    }
}
