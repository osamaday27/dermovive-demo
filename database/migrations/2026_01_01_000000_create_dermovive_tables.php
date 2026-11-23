<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->text('value')->nullable();
        });

        Schema::create('document_sequences', function (Blueprint $t) {
            $t->id();
            $t->string('type', 10)->unique();
            $t->unsignedBigInteger('last_number')->default(0);
        });
        foreach (['INV', 'RCT', 'TRX', 'CUS'] as $type) {
            DB::table('document_sequences')->insert(['type' => $type, 'last_number' => 0]);
        }

        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name');
            $t->string('phone', 30)->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->string('category', 30);
            $t->string('tax_id', 30)->nullable();
            $t->decimal('credit_limit', 15, 2)->default(0);
            $t->decimal('opening_balance', 15, 2)->default(0);
            $t->unsignedSmallInteger('payment_terms_days')->default(30);
            $t->string('status', 15)->default('active');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'category']);
        });

        Schema::create('customer_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->date('trx_date');
            $t->string('type', 30);
            $t->string('ref_no', 30)->nullable();
            $t->string('reference_type')->nullable();
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->string('description')->nullable();
            $t->decimal('debit', 15, 2)->default(0);
            $t->decimal('credit', 15, 2)->default(0);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->index(['customer_id', 'trx_date']);
            $t->index(['reference_type', 'reference_id']);
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 30)->unique();
            $t->string('name_ar');
            $t->string('name_en');
            $t->string('name_fr');
        });

        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->boolean('is_default')->default(false);
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->string('sku', 40)->unique();
            $t->string('barcode', 20)->nullable()->unique();
            $t->string('name_ar');
            $t->string('name_en');
            $t->string('name_fr');
            $t->string('brand')->nullable();
            $t->string('unit', 20);
            $t->decimal('purchase_price', 15, 2)->default(0);
            $t->decimal('selling_price', 15, 2)->default(0);
            $t->decimal('vat_rate', 5, 2)->default(14);
            $t->decimal('min_stock', 12, 3)->default(0);
            $t->string('status', 15)->default('active');
            $t->text('description')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('product_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $t->string('batch_no', 40);
            $t->date('mfg_date')->nullable();
            $t->date('expiry_date');
            $t->decimal('unit_cost', 15, 2)->default(0);
            $t->decimal('initial_qty', 12, 3)->default(0);
            $t->decimal('available_qty', 12, 3)->default(0);
            $t->timestamps();
            $t->unique(['product_id', 'batch_no']);
            $t->index('expiry_date');
        });

        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->foreignId('batch_id')->constrained('product_batches')->restrictOnDelete();
            $t->string('type', 20);
            $t->decimal('qty_in', 12, 3)->default(0);
            $t->decimal('qty_out', 12, 3)->default(0);
            $t->decimal('balance_after', 12, 3)->default(0);
            $t->string('reference_type')->nullable();
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->string('ref_no', 30)->nullable();
            $t->string('note')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->dateTime('moved_at');
            $t->timestamps();
            $t->index(['product_id', 'moved_at']);
        });

        Schema::create('treasury_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type', 15);
            $t->decimal('opening_balance', 15, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_no', 30)->unique();
            $t->string('idempotency_key', 64)->unique();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->date('invoice_date');
            $t->decimal('subtotal', 15, 2)->default(0);
            $t->decimal('discount_pct', 5, 2)->default(0);
            $t->decimal('discount_total', 15, 2)->default(0);
            $t->decimal('taxable_amount', 15, 2)->default(0);
            $t->decimal('vat_total', 15, 2)->default(0);
            $t->decimal('grand_total', 15, 2)->default(0);
            $t->decimal('paid_amount', 15, 2)->default(0);
            $t->string('payment_method', 20);
            $t->string('status', 15)->default('unpaid');
            $t->boolean('credit_override')->default(false);
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['invoice_date', 'status']);
            $t->index('customer_id');
        });

        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->foreignId('batch_id')->constrained('product_batches')->restrictOnDelete();
            $t->string('unit', 20);
            $t->decimal('quantity', 12, 3);
            $t->decimal('unit_price', 15, 2);
            $t->decimal('discount_pct', 5, 2)->default(0);
            $t->decimal('vat_rate', 5, 2)->default(0);
            $t->decimal('unit_cost', 15, 2)->default(0);      // historical cost snapshot
            $t->decimal('discount_amount', 15, 2)->default(0);
            $t->decimal('vat_amount', 15, 2)->default(0);
            $t->decimal('line_total', 15, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('invoice_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $t->string('method', 20);
            $t->decimal('amount', 15, 2);
            $t->foreignId('treasury_account_id')->nullable()->constrained('treasury_accounts')->restrictOnDelete();
            $t->date('paid_at');
            $t->string('reference')->nullable();
            $t->timestamps();
        });

        Schema::create('collection_receipts', function (Blueprint $t) {
            $t->id();
            $t->string('receipt_no', 30)->unique();
            $t->string('idempotency_key', 64)->unique();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->date('receipt_date');
            $t->decimal('amount', 15, 2);
            $t->string('method', 20);
            $t->foreignId('treasury_account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $t->string('status', 15)->default('posted');
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
            $t->index(['customer_id', 'receipt_date']);
        });

        Schema::create('collection_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('receipt_id')->constrained('collection_receipts')->restrictOnDelete();
            $t->foreignId('invoice_id')->nullable()->constrained('invoices')->restrictOnDelete(); // null = account-level
            $t->decimal('amount', 15, 2);
            $t->timestamps();
        });

        Schema::create('treasury_transactions', function (Blueprint $t) {
            $t->id();
            $t->string('tx_no', 30)->unique();
            $t->foreignId('account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $t->date('tx_date');
            $t->string('type', 20);
            $t->string('description')->nullable();
            $t->string('reference')->nullable();
            $t->string('source_type')->nullable();
            $t->unsignedBigInteger('source_id')->nullable();
            $t->string('dedupe_key', 80)->nullable()->unique();
            $t->uuid('transfer_group_id')->nullable()->index();
            $t->decimal('inflow', 15, 2)->default(0);
            $t->decimal('outflow', 15, 2)->default(0);
            $t->unsignedBigInteger('reversal_of_id')->nullable()->index();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
            $t->index(['account_id', 'tx_date', 'id']);
        });
    }

    public function down(): void
    {
        foreach (['treasury_transactions','collection_allocations','collection_receipts','invoice_payments','invoice_items','invoices','treasury_accounts','stock_movements','product_batches','products','warehouses','categories','customer_transactions','customers','document_sequences','settings'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
