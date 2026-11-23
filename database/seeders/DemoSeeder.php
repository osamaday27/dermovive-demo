<?php

namespace Database\Seeders;

use App\Actions\Collections\RecordCollectionAction;
use App\Actions\Customers\SaveCustomerAction;
use App\Actions\Sales\CompleteSaleAction;
use App\Exceptions\BusinessException;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Setting;
use App\Models\TreasuryAccount;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\CustomerLedgerService;
use App\Services\Inventory\StockService;
use App\Services\Sales\CollectionAllocator;
use App\Services\Treasury\TreasuryService;
use App\Support\Ean13;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Deterministic, repeatable demo data. History is generated THROUGH the real business actions,
 * so every ledger, stock movement and treasury entry reconciles by construction.
 */
class DemoSeeder extends Seeder
{
    private array $lowStockIdx = [3, 12, 25, 35];
    private array $expiredIdx = [7, 19, 31, 40];

    public function run(): void
    {
        mt_srand(20260101);
        $this->foundation();
        $this->products();
        $this->customers();
        if (Invoice::count() === 0) {
            $this->history();
            $this->collections();
            $this->treasuryMovements();
        }
    }

    private function foundation(): void
    {
        User::updateOrCreate(['email' => 'admin@dermovive.demo'], ['name' => 'Demo Admin', 'password' => 'password']);

        foreach ([
            'company_name' => 'Dermovive Pharma', 'company_address' => '12 Tahrir St., Dokki, Giza, Egypt',
            'company_phone' => '+20 2 3335 0000', 'company_tax_id' => '123-456-789', 'vat_rates' => '14,15',
            'allow_credit_override' => '1', 'currency' => 'EGP',
        ] as $k => $v) { Setting::put($k, $v); }

        Warehouse::firstOrCreate(['name' => 'Main Warehouse'], ['is_default' => true]);

        foreach ([
            ['skincare', 'العناية بالبشرة', 'Skincare', 'Soins de la peau'],
            ['haircare', 'العناية بالشعر', 'Haircare', 'Soins capillaires'],
            ['cosmetics', 'مستحضرات التجميل', 'Cosmetics', 'Cosmétiques'],
            ['derma', 'الجلدية', 'Derma', 'Dermatologie'],
            ['supplements', 'المكملات الغذائية', 'Supplements', 'Compléments alimentaires'],
            ['personal_care', 'العناية الشخصية', 'Personal Care', 'Soins personnels'],
        ] as [$slug, $ar, $en, $fr]) {
            Category::updateOrCreate(['slug' => $slug], ['name_ar' => $ar, 'name_en' => $en, 'name_fr' => $fr]);
        }

        $t = app(TreasuryService::class);
        $t->openAccount(['name' => 'Main Cashbox', 'type' => 'cash', 'opening_balance' => '80000.00']);
        $t->openAccount(['name' => 'Bank Account', 'type' => 'bank', 'opening_balance' => '150000.00']);
        $t->openAccount(['name' => 'Vodafone Cash', 'type' => 'wallet', 'opening_balance' => '5000.00']);
        $t->openAccount(['name' => 'InstaPay', 'type' => 'wallet', 'opening_balance' => '8000.00']);
    }

    private function productData(): array
    {
        // en | fr | ar | category | brand | unit | cost | price | vat | min
        $rows = [
            'Hyaluronic Acid Serum 30ml|Sérum à l\'acide hyaluronique 30ml|سيروم حمض الهيالورونيك ٣٠ مل|skincare|Dermovive|bottle|180|295|14|15',
            'Vitamin C Serum 30ml|Sérum Vitamine C 30ml|سيروم فيتامين سي ٣٠ مل|skincare|Dermovive|bottle|165|275|14|15',
            'Niacinamide Serum 10%|Sérum Niacinamide 10%|سيروم النياسيناميد ١٠٪|skincare|PureSkin|bottle|140|235|14|15',
            'Retinol Night Cream|Crème de nuit au rétinol|كريم الريتينول الليلي|skincare|PureSkin|tube|210|345|14|12',
            'Sunscreen SPF 50|Écran solaire SPF 50|واقي شمس SPF 50|skincare|SolShield|tube|120|210|14|25',
            'Moisturizing Cream 50ml|Crème hydratante 50ml|كريم مرطب ٥٠ مل|skincare|Aqua Derm|tube|95|165|14|20',
            'Facial Cleanser Gel|Gel nettoyant visage|جل غسول الوجه|skincare|Aqua Derm|bottle|70|125|14|20',
            'Micellar Water 400ml|Eau micellaire 400ml|ماء ميسيلار ٤٠٠ مل|skincare|Lumé|bottle|85|150|14|20',
            'Eye Contour Cream|Crème contour des yeux|كريم محيط العين|skincare|Lumé|tube|130|220|14|10',
            'Clay Face Mask|Masque visage à l\'argile|ماسك الطين للوجه|skincare|PureSkin|piece|45|85|14|15',
            'Anti-Dandruff Shampoo|Shampooing antipelliculaire|شامبو مضاد للقشرة|haircare|Hairova|bottle|60|110|14|30',
            'Hair Conditioner|Après-shampooing|بلسم الشعر|haircare|Hairova|bottle|55|98|14|25',
            'Hair Serum|Sérum capillaire|سيروم الشعر|haircare|Hairova|bottle|75|135|14|15',
            'Argan Hair Oil|Huile d\'argan cheveux|زيت الأرجان للشعر|haircare|BioLuxe|bottle|90|160|14|15',
            'Keratin Hair Mask|Masque capillaire à la kératine|ماسك الكيراتين للشعر|haircare|BioLuxe|piece|110|190|14|12',
            'Hair Growth Lotion|Lotion pousse cheveux|لوشن تكثيف الشعر|haircare|Dermovive|bottle|150|255|14|12',
            'Dry Shampoo Spray|Shampooing sec|شامبو جاف سبراي|haircare|Hairova|bottle|65|115|14|15',
            'Matte Lipstick|Rouge à lèvres mat|أحمر شفاه مات|cosmetics|Lumé|piece|50|95|15|30',
            'Liquid Foundation|Fond de teint liquide|كريم أساس سائل|cosmetics|Lumé|bottle|120|215|15|15',
            'Waterproof Mascara|Mascara waterproof|ماسكارا مقاومة للماء|cosmetics|Lumé|piece|60|110|15|20',
            'Compact Powder|Poudre compacte|بودرة مضغوطة|cosmetics|BioLuxe|piece|70|125|15|20',
            'Setting Spray|Spray fixateur|سبراي تثبيت المكياج|cosmetics|BioLuxe|bottle|80|145|15|12',
            'Eyeliner Pencil|Crayon eye-liner|قلم كحل|cosmetics|Lumé|piece|25|50|15|40',
            'Acne Treatment Gel|Gel traitement anti-acné|جل علاج حب الشباب|derma|Derma Plus|tube|95|170|14|20',
            'Eczema Soothing Cream|Crème apaisante eczéma|كريم مهدئ للإكزيما|derma|Derma Plus|tube|140|240|14|15',
            'Pigmentation Cream|Crème anti-pigmentation|كريم علاج التصبغات|derma|Derma Plus|tube|160|275|14|15',
            'Repair Barrier Balm|Baume réparateur barrière|بلسم إصلاح حاجز البشرة|derma|Dermovive Pro|tube|125|215|14|15',
            'Medicated Antifungal Cream|Crème antifongique|كريم مضاد للفطريات|derma|Derma Plus|tube|55|99|14|25',
            'Scar Silicone Gel|Gel silicone cicatrices|جل سيليكون للندبات|derma|Dermovive Pro|tube|150|260|14|10',
            'Rosacea Calming Serum|Sérum apaisant rosacée|سيروم مهدئ للوردية|derma|Dermovive Pro|bottle|175|295|14|10',
            'Collagen Supplements|Compléments de collagène|مكمل الكولاجين|supplements|VitaCore|box|310|495|14|20',
            'Multivitamins|Multivitamines|فيتامينات متعددة|supplements|VitaCore|box|120|210|14|30',
            'Biotin 10000mcg|Biotine 10000mcg|بيوتين ١٠٠٠٠ ميكروجرام|supplements|VitaCore|bottle|95|165|14|25',
            'Omega-3 Capsules|Capsules oméga-3|كبسولات أوميجا ٣|supplements|VitaCore|bottle|140|235|14|20',
            'Vitamin D3|Vitamine D3|فيتامين د٣|supplements|VitaCore|box|60|110|14|30',
            'Zinc + Vitamin C|Zinc + Vitamine C|زنك + فيتامين سي|supplements|VitaCore|box|50|95|14|30',
            'Iron Folic Complex|Complexe fer et acide folique|مركب الحديد وحمض الفوليك|supplements|VitaCore|box|65|120|14|25',
            'Body Lotion 400ml|Lait corporel 400ml|لوشن للجسم ٤٠٠ مل|personal_care|Aqua Derm|bottle|80|140|14|25',
            'Hand Cream|Crème pour les mains|كريم اليدين|personal_care|Aqua Derm|tube|35|65|14|30',
            'Deodorant Roll-On|Déodorant bille|مزيل عرق رول|personal_care|Nilea|piece|40|75|14|30',
            'Lip Balm SPF 15|Baume à lèvres SPF 15|مرطب شفاه SPF 15|personal_care|Nilea|piece|22|45|14|40',
            'Shower Gel 500ml|Gel douche 500ml|جل استحمام ٥٠٠ مل|personal_care|Nilea|bottle|60|105|14|25',
        ];
        return array_map(fn ($r) => explode('|', $r), $rows);
    }

    private function products(): void
    {
        $stock = app(StockService::class);
        $cats = Category::pluck('id', 'slug');
        $wh = Warehouse::default();
        $today = Carbon::today();
        $at = $today->copy()->subDays(120)->setTime(9, 0);

        foreach ($this->productData() as $i => [$en, $fr, $ar, $cat, $brand, $unit, $cost, $price, $vat, $min]) {
            $sku = 'DRM-'.strtoupper(substr($cat, 0, 3)).'-'.sprintf('%03d', $i + 1);
            $p = Product::withTrashed()->updateOrCreate(['sku' => $sku], [
                'category_id' => $cats[$cat], 'barcode' => Ean13::make('622'.sprintf('%09d', 100000000 + $i * 7919)),
                'name_en' => $en, 'name_fr' => $fr, 'name_ar' => $ar, 'brand' => $brand, 'unit' => $unit,
                'purchase_price' => $cost, 'selling_price' => $price, 'vat_rate' => $vat, 'min_stock' => $min,
                'status' => 'active', 'description' => $en.' — '.$brand,
            ]);
            if (ProductBatch::where('product_id', $p->id)->exists()) { continue; }

            $mk = function (string $letter, int $expiryDays, int $qty) use ($stock, $p, $i, $today, $at) {
                $exp = $today->copy()->addDays($expiryDays);
                $stock->addBatch($p, [
                    'batch_no' => sprintf('LOT-%s-%02d%s', $exp->copy()->subYears(2)->format('ym'), $i + 1, $letter),
                    'mfg_date' => $exp->copy()->subMonths(30)->toDateString(), 'expiry_date' => $exp->toDateString(),
                    'unit_cost' => $p->purchase_price, 'qty' => $qty, 'moved_at' => $at,
                ]);
            };

            if (in_array($i, $this->lowStockIdx, true)) { $mk('A', mt_rand(200, 500), mt_rand(5, 12)); continue; }
            $mk('A', mt_rand(180, 600), mt_rand(80, 150));
            if ($i % 3 === 0) { $mk('B', mt_rand(400, 700), mt_rand(60, 100)); }
            if ($i % 6 === 1) { $mk('C', mt_rand(8, 27), mt_rand(25, 40)); }          // critical (<=30d)
            if ($i % 5 === 2) { $mk('D', mt_rand(40, 85), mt_rand(30, 50)); }          // warning (<=90d)
            if (in_array($i, $this->expiredIdx, true)) { $mk('X', -mt_rand(6, 35), 120); } // expired, still on shelf
        }
    }

    private function customers(): void
    {
        $action = app(SaveCustomerAction::class);
        $cities = ['Cairo', 'Giza', 'Alexandria', 'Mansoura', 'Tanta', 'Assiut', 'Luxor', 'Zagazig', 'Port Said', 'Fayoum'];
        // name | category | credit | terms | opening
        $list = [
            ['Walk-in Customer', 'retail', 0, 0, 0],
            ['Al Nour Pharmacy', 'pharmacy', 120000, 30, 12500], ['El Shifa Pharmacy', 'pharmacy', 100000, 30, 0],
            ['Cairo Care Pharmacy', 'pharmacy', 150000, 30, 8200], ['Nile Health Pharmacy', 'pharmacy', 90000, 30, 0],
            ['Dar El Dawaa Pharmacy', 'pharmacy', 110000, 30, 0], ['Al Amal Pharmacy', 'pharmacy', 80000, 30, 4300],
            ['El Hayah Pharmacy', 'pharmacy', 95000, 30, 0], ['Seif El Din Pharmacy', 'pharmacy', 85000, 30, 0],
            ['Glow Beauty Center', 'beauty_center', 60000, 15, 0], ['Lotus Beauty Lounge', 'beauty_center', 50000, 15, 6100],
            ['Cleopatra Skin Clinic', 'beauty_center', 80000, 15, 0], ['Nefertiti Beauty Center', 'beauty_center', 45000, 15, 0],
            ['Rose Garden Spa', 'beauty_center', 40000, 15, 0], ['Luxor Derma Clinic', 'beauty_center', 70000, 15, 15000],
            ['Delta Pharma Trading', 'wholesale', 300000, 45, 25000], ['Al Madina Wholesale', 'wholesale', 250000, 45, 0],
            ['Upper Egypt Health Supplies', 'wholesale', 280000, 45, 0], ['Sinai Wholesale Hub', 'wholesale', 220000, 45, 0],
            ['Tanta Medical Wholesale', 'wholesale', 260000, 45, 9800],
            ['Beauty Corner Shop', 'retail', 25000, 7, 0], ['Zamalek Cosmetics Store', 'retail', 30000, 7, 0],
            ['Maadi Care Boutique', 'retail', 28000, 7, 0],
            ['Alex Medical Distribution', 'distributor', 400000, 60, 0], ['Delta Cosmetics Distribution', 'distributor', 380000, 60, 18000],
        ];
        foreach ($list as $i => [$name, $cat, $credit, $terms, $opening]) {
            if (Customer::where('name', $name)->exists()) { continue; }
            $action->execute([
                'name' => $name, 'category' => $cat, 'credit_limit' => $credit, 'payment_terms_days' => $terms,
                'opening_balance' => $opening, 'opening_date' => Carbon::today()->subDays(120)->toDateString(),
                'phone' => '010'.sprintf('%08d', 20000000 + ($i * 2654321) % 70000000),
                'email' => strtolower(preg_replace('/[^a-z]+/i', '.', $name)).'@example.test',
                'address' => (10 + $i).' Demo St., '.$cities[$i % count($cities)].', Egypt',
                'tax_id' => $i ? sprintf('%03d-%03d-%03d', 100 + $i, 200 + $i, 300 + $i) : null,
                'notes' => null,
            ]);
        }
    }

    private function history(): void
    {
        $sale = app(CompleteSaleAction::class);
        $accounts = TreasuryAccount::pluck('id', 'name');
        $customers = Customer::where('status', 'active')->get();
        $walkIn = $customers->firstWhere('name', 'Walk-in Customer');
        $pool = Product::active()->orderBy('id')->get()->values()->filter(fn ($p, $k) => ! in_array($k, $this->lowStockIdx, true))->values();
        $bulk = ['wholesale', 'distributor'];

        for ($i = 0; $i < 70; $i++) {
            $daysAgo = (int) round(88 - $i * (88 / 70));
            $date = Carbon::today()->subDays($daysAgo)->toDateString();
            $isWalk = mt_rand(1, 100) <= 22;
            $cust = $isWalk ? $walkIn : $customers->where('id', '!=', $walkIn->id)->values()->get(mt_rand(0, $customers->count() - 2));
            $mult = in_array($cust->category, $bulk, true) ? 4 : 1;

            $lines = []; $used = [];
            for ($n = mt_rand(1, 5); $n > 0; $n--) {
                $p = $pool[mt_rand(0, $pool->count() - 1)];
                if (isset($used[$p->id])) { continue; }
                $used[$p->id] = true;
                $lines[] = ['product_id' => $p->id, 'qty' => mt_rand(1, 5) * $mult, 'price' => $p->selling_price, 'vat' => $p->vat_rate,
                    'discount' => mt_rand(1, 10) <= 2 ? 5 : 0, 'unit' => $p->unit];
            }

            $scenario = $isWalk ? 0 : $i % 5;
            $method = ['cash', 'card', 'bank_transfer'][$i % 3];
            $data = [
                'customer_id' => $cust->id, 'date' => $date, 'lines' => $lines, 'idempotency_key' => 'seed-inv-'.$i,
                'invoice_discount_pct' => in_array($cust->category, $bulk, true) ? [0, 3, 5][$i % 3] : 0,
                'payment_method' => $method, 'paid_amount' => '',
                'treasury_account_id' => match ($method) {
                    'cash' => $accounts['Main Cashbox'],
                    'card' => $accounts['Bank Account'],
                    default => [$accounts['Bank Account'], $accounts['InstaPay'], $accounts['Vodafone Cash']][$i % 3],
                },
            ];
            if ($scenario === 3) { $data['payment_method'] = 'credit'; $data['treasury_account_id'] = null; }
            if ($scenario === 2) { $data['payment_method'] = 'cash'; $data['treasury_account_id'] = $accounts['Main Cashbox']; $data['paid_amount'] = '__partial__'; }

            $this->attempt($sale, $data, $accounts);
        }
    }

    private function attempt(CompleteSaleAction $sale, array $data, $accounts): void
    {
        $partial = ($data['paid_amount'] ?? '') === '__partial__';
        try {
            if ($partial) {
                // estimate total to pay roughly half
                $est = '0';
                foreach ($data['lines'] as $l) { $est = Money::add($est, Money::mul($l['qty'], $l['price'])); }
                $data['paid_amount'] = Money::round(Money::mul($est, '0.5'));
            }
            $sale->execute($data);
        } catch (BusinessException $e) {
            // credit limit / stock hiccup: fall back to a fully paid cash sale with 1 unit per line
            foreach ($data['lines'] as &$l) { $l['qty'] = 1; }
            $data = array_merge($data, ['lines' => $data['lines'], 'payment_method' => 'cash', 'paid_amount' => '', 'treasury_account_id' => $accounts['Main Cashbox']]);
            try { $sale->execute($data); } catch (BusinessException) { /* skip */ }
        }
    }

    private function collections(): void
    {
        $action = app(RecordCollectionAction::class);
        $ledger = app(CustomerLedgerService::class);
        $alloc = app(CollectionAllocator::class);
        $acc = TreasuryAccount::pluck('id', 'name');
        $k = 0;

        foreach (Customer::where('name', '!=', 'Walk-in Customer')->orderBy('id')->get() as $c) {
            $balance = $ledger->balance($c);
            if (! Money::gt($balance, '0') || mt_rand(1, 10) > 8) { continue; }
            $rounds = mt_rand(1, 2);
            for ($r = 0; $r < $rounds; $r++) {
                $balance = $ledger->balance($c);
                if (! Money::gt($balance, '100')) { break; }
                $frac = [0.35, 0.5, 0.7, 1.0][mt_rand(0, 3)];
                $amount = Money::round(Money::mul($balance, (string) $frac));
                $date = Carbon::today()->subDays(mt_rand(0, 20));
                $method = ['cash', 'bank_transfer', 'check'][$k % 3];
                $account = match ($method) {
                    'cash' => $acc['Main Cashbox'],
                    'bank_transfer' => [$acc['Bank Account'], $acc['InstaPay'], $acc['Vodafone Cash']][$k % 3],
                    default => $acc['Bank Account'],
                };
                $action->execute([
                    'customer_id' => $c->id, 'date' => $date->toDateString(), 'amount' => $amount, 'method' => $method,
                    'treasury_account_id' => $account, 'allocations' => $alloc->suggest($c->id, $amount, $date),
                    'idempotency_key' => 'seed-rct-'.$c->id.'-'.$r, 'notes' => null,
                ]);
                $k++;
            }
        }
    }

    private function treasuryMovements(): void
    {
        $t = app(TreasuryService::class);
        $acc = TreasuryAccount::pluck('id', 'name');
        $day = fn (int $n) => Carbon::today()->subDays($n)->toDateString();

        $t->cashIn($acc['Bank Account'], 50000, $day(100), 'Owner capital deposit', 'DEP-001');
        foreach ([[80, 12000, 'Warehouse & office rent'], [70, 1850, 'Electricity bill'], [61, 950, 'Transport & delivery'],
                  [52, 2400, 'Packaging materials'], [44, 650, 'Office supplies'], [31, 1100, 'Delivery vehicle fuel'],
                  [20, 600, 'Internet & phones'], [12, 3500, 'Marketing & samples'], [5, 950, 'Transport & delivery']] as [$d, $amt, $desc]) {
            $t->cashOut($acc['Main Cashbox'], $amt, $day($d), $desc, 'EXP');
        }
        $t->transfer($acc['Main Cashbox'], $acc['Bank Account'], 20000, $day(60), 'Cash deposit to bank');
        $t->transfer($acc['Main Cashbox'], $acc['Bank Account'], 15000, $day(35), 'Cash deposit to bank');
        $t->transfer($acc['Bank Account'], $acc['InstaPay'], 5000, $day(18), 'Top-up wallet');
        $t->transfer($acc['Main Cashbox'], $acc['Bank Account'], 25000, $day(10), 'Cash deposit to bank');
    }
}
