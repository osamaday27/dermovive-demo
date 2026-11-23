<?php

namespace Tests\Support;

use App\Actions\Customers\SaveCustomerAction;
use App\Actions\Sales\CompleteSaleAction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Setting;
use App\Models\TreasuryAccount;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use App\Services\Treasury\TreasuryService;

trait Fixtures
{
    protected function basics(): void
    {
        Setting::put('vat_rates', '14,15');
        Setting::put('allow_credit_override', '0');
        Warehouse::default();
        Category::firstOrCreate(['slug' => 'skincare'], ['name_ar' => 'بشرة', 'name_en' => 'Skincare', 'name_fr' => 'Soins']);
    }

    protected function product(array $o = []): Product
    {
        $this->basics();
        static $n = 0; $n++;
        return Product::create($o + [
            'category_id' => Category::first()->id, 'sku' => 'T-'.$n, 'barcode' => null, 'name_ar' => 'منتج', 'name_en' => 'Product '.$n, 'name_fr' => 'Produit',
            'unit' => 'piece', 'purchase_price' => '50.00', 'selling_price' => '100.00', 'vat_rate' => '14', 'min_stock' => '5', 'status' => 'active',
        ]);
    }

    protected function batch(Product $p, int $qty, int $expiryInDays = 365, string $cost = '50.00', ?string $no = null): ProductBatch
    {
        static $n = 0; $n++;
        return app(StockService::class)->addBatch($p, [
            'batch_no' => $no ?? 'LOT-'.$n, 'expiry_date' => today()->addDays($expiryInDays)->toDateString(), 'unit_cost' => $cost, 'qty' => $qty,
        ]);
    }

    protected function customer(array $o = []): Customer
    {
        static $n = 0; $n++;
        return app(SaveCustomerAction::class)->execute($o + [
            'name' => 'Customer '.$n, 'category' => 'pharmacy', 'credit_limit' => '1000', 'payment_terms_days' => 30, 'opening_balance' => '0',
        ]);
    }

    protected function account(string $name = 'Main Cashbox', string $type = 'cash', string $opening = '0'): TreasuryAccount
    {
        return app(TreasuryService::class)->openAccount(['name' => $name, 'type' => $type, 'opening_balance' => $opening]);
    }

    protected function sale(Customer $c, Product $p, int $qty = 1, array $o = []): Invoice
    {
        return app(CompleteSaleAction::class)->execute($o + [
            'customer_id' => $c->id, 'date' => today()->toDateString(),
            'lines' => [['product_id' => $p->id, 'qty' => $qty, 'discount' => 0, 'vat' => '14']],
            'payment_method' => 'credit', 'paid_amount' => '', 'treasury_account_id' => null,
            'idempotency_key' => uniqid('t', true),
        ]);
    }
}
