<?php

namespace App\Actions\Customers;

use App\Enums\LedgerType;
use App\Models\Customer;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Accounting\DocumentNumberService;
use App\Support\Money;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveCustomerAction
{
    public function __construct(private DocumentNumberService $docs, private CustomerLedgerService $ledger)
    {
    }

    public function execute(array $d, ?int $id = null): Customer
    {
        return DB::transaction(function () use ($d, $id) {
            $fields = Arr::only($d, ['name', 'phone', 'email', 'address', 'category', 'tax_id', 'credit_limit', 'payment_terms_days', 'notes']);
            if ($id) {
                $c = Customer::findOrFail($id);
                $c->update($fields);   // opening balance is immutable after creation (it lives in the ledger)
                return $c;
            }
            $opening = Money::round(Money::num($d['opening_balance'] ?? 0));
            $c = Customer::create($fields + [
                'code' => $this->docs->next('CUS', 4),
                'opening_balance' => $opening,
                'status' => 'active',
            ]);
            if (! Money::eq($opening, '0')) {
                $isDebit = Money::gt($opening, '0');
                $abs = ltrim($opening, '-');
                $this->ledger->post($c, LedgerType::Opening->value, $isDebit ? $abs : '0', $isDebit ? '0' : $abs, $d['opening_date'] ?? today(), 'OPEN', null, t('stmt.opening_entry'));
            }
            return $c;
        });
    }
}
