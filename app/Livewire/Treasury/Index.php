<?php

namespace App\Livewire\Treasury;

use App\Enums\TreasuryTxType;
use App\Exceptions\BusinessException;
use App\Livewire\Concerns\HasFilters;
use App\Models\TreasuryAccount;
use App\Models\TreasuryTransaction;
use App\Services\Treasury\TreasuryService;
use Livewire\Component;

class Index extends Component
{
    use HasFilters;

    public string $account = '';
    public string $from = '';
    public string $to = '';
    public string $type = '';
    protected array $resetOn = ['account', 'from', 'to', 'type'];

    public string $modal = '';   // in | out | transfer
    public array $form = [];

    public function open(string $kind): void
    {
        abort_unless(in_array($kind, ['in', 'out', 'transfer'], true), 404);
        $this->resetValidation();
        $ids = TreasuryAccount::where('is_active', true)->orderBy('id')->pluck('id');
        $this->form = ['account_id' => (string) $ids->first(), 'to_account_id' => (string) ($ids[1] ?? ''), 'amount' => '',
            'date' => today()->toDateString(), 'description' => '', 'reference' => ''];
        $this->modal = $kind;
    }

    public function closeModal(): void { $this->modal = ''; }

    public function save(TreasuryService $t): void
    {
        $rules = [
            'form.account_id' => 'required|exists:treasury_accounts,id',
            'form.amount' => 'required|numeric|gt:0|max:1000000000',
            'form.date' => 'required|date',
            'form.description' => $this->modal === 'transfer' ? 'nullable|string|max:200' : 'required|string|max:200',
            'form.reference' => 'nullable|string|max:60',
        ];
        if ($this->modal === 'transfer') { $rules['form.to_account_id'] = 'required|exists:treasury_accounts,id|different:form.account_id'; }
        $this->validate($rules);
        $f = $this->form;
        try {
            match ($this->modal) {
                'in' => $t->cashIn((int) $f['account_id'], $f['amount'], $f['date'], $f['description'], $f['reference'] ?: null),
                'out' => $t->cashOut((int) $f['account_id'], $f['amount'], $f['date'], $f['description'], $f['reference'] ?: null),
                'transfer' => $t->transfer((int) $f['account_id'], (int) $f['to_account_id'], $f['amount'], $f['date'], $f['description'] ?: null),
            };
        } catch (BusinessException $e) {
            $this->addError('business', $e->getMessage());
            return;
        }
        $this->notify(t('treas.saved'));
        $this->modal = '';
    }

    public function reverse(int $id, TreasuryService $t): void
    {
        try { $t->reverse($id); $this->notify(t('treas.reversed')); }
        catch (BusinessException $e) { $this->notify($e->getMessage(), 'error'); }
    }

    public function render()
    {
        $svc = app(TreasuryService::class);
        $accounts = TreasuryAccount::where('is_active', true)->orderBy('id')->get()->map(function ($a) use ($svc) {
            $s = TreasuryTransaction::where('account_id', $a->id)->where('type', '!=', TreasuryTxType::Opening->value)
                ->selectRaw('COALESCE(SUM(inflow),0) as i, COALESCE(SUM(outflow),0) as o')->first();
            $a->balance = $svc->balance($a); $a->in = $s->i; $a->out = $s->o;
            return $a;
        });

        $running = '(SELECT COALESCE(SUM(t2.inflow - t2.outflow),0) FROM treasury_transactions t2 WHERE t2.account_id = treasury_transactions.account_id AND (t2.tx_date < treasury_transactions.tx_date OR (t2.tx_date = treasury_transactions.tx_date AND t2.id <= treasury_transactions.id))) as running';
        $txs = TreasuryTransaction::with('account')->select('treasury_transactions.*')->selectRaw($running)
            ->when($this->account !== '', fn ($q) => $q->where('account_id', (int) $this->account))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->when($this->from !== '', fn ($q) => $q->where('tx_date', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->where('tx_date', '<=', $this->to))
            ->orderByDesc('tx_date')->orderByDesc('id')->paginate(12);

        return view('livewire.treasury.index', [
            'accounts' => $accounts, 'txs' => $txs, 'types' => TreasuryTxType::cases(),
            'reversed' => TreasuryTransaction::whereIn('reversal_of_id', $txs->pluck('id'))->pluck('reversal_of_id')->all(),
            'manual' => [TreasuryTxType::CashIn->value, TreasuryTxType::CashOut->value, TreasuryTxType::TransferIn->value, TreasuryTxType::TransferOut->value],
            'total' => \App\Support\Money::sum($accounts->pluck('balance')),
        ])->title(t('nav.treasury'));
    }
}
