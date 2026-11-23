<?php

namespace App\Livewire\Inventory;

use App\Enums\ProductUnit;
use App\Exceptions\BusinessException;
use App\Livewire\Concerns\HasFilters;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Setting;
use App\Services\Inventory\StockService;
use App\Services\Reports\ReportService;
use App\Support\Ean13;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    use HasFilters;

    public string $search = '';
    public string $category = '';
    public string $expiry = '';
    public string $stock = '';
    protected array $resetOn = ['search', 'category', 'expiry', 'stock'];

    public bool $showForm = false;
    #[Locked] public ?int $editingId = null;
    #[Locked] public ?int $detailId = null;
    #[Locked] public ?int $labelId = null;
    public bool $showBatch = false;
    public bool $showAdjust = false;
    public array $form = [];
    public array $batchForm = [];
    public array $adjustForm = [];

    public function create(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = ['sku' => '', 'barcode' => '', 'name_ar' => '', 'name_en' => '', 'name_fr' => '', 'category_id' => (string) Category::value('id'),
            'brand' => '', 'unit' => 'piece', 'purchase_price' => '0', 'selling_price' => '0', 'vat_rate' => Setting::vatRates()[0] ?? '14',
            'min_stock' => '0', 'status' => 'active', 'description' => ''];
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->resetValidation();
        $p = Product::findOrFail($id);
        $this->editingId = $p->id;
        $this->form = [
            'sku' => $p->sku, 'barcode' => (string) $p->barcode, 'name_ar' => $p->name_ar, 'name_en' => $p->name_en, 'name_fr' => $p->name_fr,
            'category_id' => (string) $p->category_id, 'brand' => (string) $p->brand, 'unit' => $p->unit,
            'purchase_price' => (string) $p->purchase_price, 'selling_price' => (string) $p->selling_price,
            'vat_rate' => (string) (float) $p->vat_rate, 'min_stock' => (string) (float) $p->min_stock, 'status' => $p->status, 'description' => (string) $p->description,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form.sku' => ['required', 'string', 'max:40', Rule::unique('products', 'sku')->ignore($this->editingId)],
            'form.barcode' => ['nullable', 'digits_between:8,14', Rule::unique('products', 'barcode')->ignore($this->editingId)],
            'form.name_ar' => 'required|string|max:150', 'form.name_en' => 'required|string|max:150', 'form.name_fr' => 'required|string|max:150',
            'form.category_id' => 'required|exists:categories,id',
            'form.brand' => 'nullable|string|max:100',
            'form.unit' => ['required', Rule::in(ProductUnit::values())],
            'form.purchase_price' => 'required|numeric|min:0|max:100000000', 'form.selling_price' => 'required|numeric|min:0|max:100000000',
            'form.vat_rate' => ['required', Rule::in(Setting::vatRates())],
            'form.min_stock' => 'required|numeric|min:0|max:10000000',
            'form.status' => ['required', Rule::in(['active', 'archived'])],
            'form.description' => 'nullable|string|max:1000',
        ]);
        $f = $this->form;
        $data = [
            'sku' => $f['sku'], 'barcode' => $f['barcode'] ?: null, 'name_ar' => $f['name_ar'], 'name_en' => $f['name_en'], 'name_fr' => $f['name_fr'],
            'category_id' => (int) $f['category_id'], 'brand' => $f['brand'] ?: null, 'unit' => $f['unit'],
            'purchase_price' => Money::round($f['purchase_price']), 'selling_price' => Money::round($f['selling_price']),
            'vat_rate' => $f['vat_rate'], 'min_stock' => $f['min_stock'], 'status' => $f['status'], 'description' => $f['description'] ?: null,
        ];
        $this->editingId ? Product::findOrFail($this->editingId)->update($data) : Product::create($data);
        $this->notify(t('inv.saved'));
        $this->closeModal();
    }

    public function show(int $id): void { $this->detailId = Product::findOrFail($id)->id; }
    public function label(int $id): void { $this->labelId = Product::findOrFail($id)->id; }

    public function newBatch(): void
    {
        $this->resetValidation();
        $p = Product::findOrFail($this->detailId);
        $this->batchForm = ['batch_no' => '', 'mfg_date' => '', 'expiry_date' => '', 'unit_cost' => (string) $p->purchase_price, 'qty' => ''];
        $this->showBatch = true;
    }

    public function saveBatch(StockService $stock): void
    {
        $p = Product::findOrFail($this->detailId);
        $this->validate([
            'batchForm.batch_no' => ['required', 'string', 'max:40', Rule::unique('product_batches', 'batch_no')->where('product_id', $p->id)],
            'batchForm.mfg_date' => 'nullable|date',
            'batchForm.expiry_date' => 'required|date|after:batchForm.mfg_date',
            'batchForm.unit_cost' => 'required|numeric|min:0',
            'batchForm.qty' => 'required|numeric|gt:0|max:10000000',
        ]);
        $stock->addBatch($p, $this->batchForm);
        $this->notify(t('inv.batch_saved'));
        $this->showBatch = false;
    }

    public function adjust(int $batchId): void
    {
        $this->resetValidation();
        $b = ProductBatch::where('product_id', $this->detailId)->findOrFail($batchId);
        $this->adjustForm = ['batch_id' => $b->id, 'direction' => 'in', 'qty' => '', 'note' => ''];
        $this->showAdjust = true;
    }

    public function saveAdjust(StockService $stock): void
    {
        $this->validate([
            'adjustForm.direction' => ['required', Rule::in(['in', 'out'])],
            'adjustForm.qty' => 'required|numeric|gt:0|max:10000000',
            'adjustForm.note' => 'required|string|max:200',
        ]);
        $b = ProductBatch::where('product_id', $this->detailId)->findOrFail($this->adjustForm['batch_id']);
        try {
            $stock->adjust($b->id, $this->adjustForm['direction'], $this->adjustForm['qty'], $this->adjustForm['note']);
        } catch (BusinessException $e) {
            $this->addError('adjustForm.qty', $e->getMessage());
            return;
        }
        $this->notify(t('inv.adjusted'));
        $this->showAdjust = false;
    }

    public function closeModal(): void
    {
        $this->showForm = false; $this->detailId = null; $this->labelId = null; $this->showBatch = false; $this->showAdjust = false;
    }

    public function closeSub(): void { $this->showBatch = false; $this->showAdjust = false; }

    private function expiryFilter(Builder $q): void
    {
        $t = Carbon::today();
        $has = fn (callable $c) => $q->whereHas('batches', fn ($b) => $c($b->where('available_qty', '>', 0)));
        match ($this->expiry) {
            'expired' => $has(fn ($b) => $b->where('expiry_date', '<', $t->toDateString())),
            'critical' => $has(fn ($b) => $b->whereBetween('expiry_date', [$t->toDateString(), $t->copy()->addDays(30)->toDateString()])),
            'warning' => $has(fn ($b) => $b->whereBetween('expiry_date', [$t->copy()->addDays(31)->toDateString(), $t->copy()->addDays(90)->toDateString()])),
            'near' => $has(fn ($b) => $b->whereBetween('expiry_date', [$t->toDateString(), $t->copy()->addDays(90)->toDateString()])),
            'valid' => $has(fn ($b) => $b->where('expiry_date', '>', $t->copy()->addDays(90)->toDateString())),
            default => null,
        };
    }

    public function render()
    {
        $products = Product::with(['category', 'batches' => fn ($q) => $q->where('available_qty', '>', 0)->orderBy('expiry_date')])
            ->withSum('batches as stock', 'available_qty')
            ->when($this->search !== '', fn ($q) => $q->where(function ($w) {
                $like = self::like($this->search);
                $w->where('name_en', 'like', $like)->orWhere('name_ar', 'like', $like)->orWhere('name_fr', 'like', $like)
                  ->orWhere('sku', 'like', $like)->orWhere('barcode', 'like', $like);
            }))
            ->when($this->category !== '', fn ($q) => $q->where('category_id', $this->category))
            ->when($this->stock === 'out', fn ($q) => $q->whereRaw(Product::STOCK_SQL.' <= 0'))
            ->when($this->stock === 'low', fn ($q) => $q->whereRaw(Product::STOCK_SQL.' > 0 and '.Product::STOCK_SQL.' <= products.min_stock'))
            ->when($this->stock === 'ok', fn ($q) => $q->whereRaw(Product::STOCK_SQL.' > products.min_stock'))
            ->tap(fn ($q) => $this->expiry !== '' ? $this->expiryFilter($q) : null)
            ->orderBy('name_en')->paginate(12);

        $detail = null;
        if ($this->detailId) {
            $detail = Product::with(['category', 'batches' => fn ($q) => $q->orderBy('expiry_date'),
                'movements' => fn ($q) => $q->with('batch')->orderByDesc('moved_at')->orderByDesc('id')->limit(15)])->findOrFail($this->detailId);
        }
        $label = $this->labelId ? Product::findOrFail($this->labelId) : null;

        return view('livewire.inventory.index', [
            'products' => $products, 'categories' => Category::orderBy('id')->get(), 'detail' => $detail, 'label' => $label,
            'summary' => app(ReportService::class)->inventory(),
            'barcodeSvg' => $label ? Ean13::svg($label->barcode) : '',
            'vatRates' => Setting::vatRates(),
        ])->title(t('nav.inventory'));
    }
}
