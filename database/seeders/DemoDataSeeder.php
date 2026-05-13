<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Expense;
use App\Models\IncomeEntry;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Product;
use App\Models\ProductCurrencyPrice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * بيانات تجريبية عربية (عملاء، موردون، منتجات، فواتير، دفعات، أوامر شراء).
 * آمن للتكرار: يعتمد على مفاتيح legacy ثابتة وupdateOrCreate.
 *
 * تشغيل: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::query()->updateOrCreate(
                ['email' => 'demo@baitpait.local'],
                [
                    'full_name' => 'مستخدم تجريبي',
                    'password' => 'password',
                    'role' => 'manager',
                    'is_active' => true,
                ]
            );

            $c1 = Client::query()->updateOrCreate(
                ['legacy_match_key' => 'demo:client:studio-north'],
                [
                    'business_name' => 'استوديو الشمال للإعلام',
                    'email' => 'billing@demo-studio-north.local',
                    'phone_primary' => '+972-50-000-0001',
                    'city' => 'نابلس',
                    'assigned_user_id' => $user->id,
                ]
            );

            $c2 = Client::query()->updateOrCreate(
                ['legacy_match_key' => 'demo:client:identity-co'],
                [
                    'business_name' => 'شركة الهوية الرقمية',
                    'email' => 'finance@demo-identity.local',
                    'phone_primary' => '+972-50-000-0002',
                    'city' => 'رام الله',
                    'assigned_user_id' => $user->id,
                ]
            );

            $c3 = Client::query()->updateOrCreate(
                ['legacy_match_key' => 'demo:client:retail-one'],
                [
                    'business_name' => 'متجر واحد — تجارة إلكترونية',
                    'email' => 'ops@demo-retail.local',
                    'phone_primary' => '+972-50-000-0003',
                    'city' => 'الخليل',
                    'assigned_user_id' => $user->id,
                ]
            );

            $s1 = Supplier::query()->updateOrCreate(
                ['legacy_number' => 'DEMO-SUP-01'],
                [
                    'business_name' => 'مورد الطباعة السريعة',
                    'email' => 'orders@demo-print.local',
                    'phone_primary' => '+972-52-000-0101',
                    'city' => 'بيت لحم',
                    'assigned_user_id' => $user->id,
                ]
            );

            $s2 = Supplier::query()->updateOrCreate(
                ['legacy_number' => 'DEMO-SUP-02'],
                [
                    'business_name' => 'استوديو التصوير الجنوبي',
                    'email' => 'studio@demo-photo.local',
                    'phone_primary' => '+972-52-000-0102',
                    'city' => 'غزة',
                    'assigned_user_id' => $user->id,
                ]
            );

            $p1 = $this->upsertProduct('DEMO-PRD-01', 'تصميم شعار + دليل استخدام', 1200, 800, 1500);
            $p2 = $this->upsertProduct('DEMO-PRD-02', 'حزمة سوشيال شهرية (12 منشوراً)', 2500, 1800, 3200);
            $p3 = $this->upsertProduct('DEMO-PRD-03', 'تصوير فوتوغرافي يوم كامل', 1800, 900, 2200);

            $doc = Carbon::now()->subDays(14)->toDateString();

            $inv1 = Invoice::query()->updateOrCreate(
                ['legacy_invoice_no' => 'DEMO-INV-001'],
                [
                    'client_id' => $c1->id,
                    'document_date' => $doc,
                    'issue_date' => $doc,
                    'due_date' => Carbon::parse($doc)->addDays(30)->toDateString(),
                    'currency_code' => 'ILS',
                    'discount_amount' => 0,
                    'total_amount' => 4700,
                    'status' => 'issued',
                    'notes' => 'فاتورة تجريبية — حزمة هوية بصرية',
                    'recorded_by_user_id' => $user->id,
                ]
            );
            $this->replaceInvoiceLines($inv1, [
                ['product_id' => $p1->id, 'order' => 1, 'title' => $p1->name, 'unit' => 1500, 'qty' => 1, 'total' => 1500],
                ['product_id' => $p2->id, 'order' => 2, 'title' => $p2->name, 'unit' => 3200, 'qty' => 1, 'total' => 3200],
            ]);

            $inv2 = Invoice::query()->updateOrCreate(
                ['legacy_invoice_no' => 'DEMO-INV-002'],
                [
                    'client_id' => $c2->id,
                    'document_date' => Carbon::now()->subDays(7)->toDateString(),
                    'currency_code' => 'ILS',
                    'discount_amount' => 200,
                    'total_amount' => 2000,
                    'status' => 'issued',
                    'recorded_by_user_id' => $user->id,
                ]
            );
            $this->replaceInvoiceLines($inv2, [
                ['product_id' => $p3->id, 'order' => 1, 'title' => $p3->name, 'unit' => 2200, 'qty' => 1, 'total' => 2200],
            ]);

            ClientPayment::query()->updateOrCreate(
                [
                    'client_id' => $c1->id,
                    'notes' => 'DEMO-PAY-CLIENT-001',
                ],
                [
                    'amount' => 2000,
                    'currency_code' => 'ILS',
                    'paid_at' => Carbon::now()->subDays(5),
                    'method' => 'تحويل بنكي',
                    'recorded_by_user_id' => $user->id,
                ]
            );

            $po = PurchaseOrder::query()->updateOrCreate(
                ['legacy_po_no' => 'DEMO-PO-001'],
                [
                    'supplier_id' => $s1->id,
                    'document_date' => Carbon::now()->subDays(10)->toDateString(),
                    'currency_code' => 'ILS',
                    'discount_amount' => 0,
                    'total_amount' => 4500,
                    'status' => 'issued',
                    'notes' => 'أمر شراء تجريبي — ورق وطباعة',
                    'recorded_by_user_id' => $user->id,
                ]
            );
            PurchaseOrderLine::query()->where('purchase_order_id', $po->id)->delete();
            PurchaseOrderLine::query()->create([
                'purchase_order_id' => $po->id,
                'line_order' => 1,
                'title' => 'ورق فاخر A3',
                'unit_price' => 1500,
                'quantity' => 2,
                'line_total' => 3000,
            ]);
            PurchaseOrderLine::query()->create([
                'purchase_order_id' => $po->id,
                'line_order' => 2,
                'title' => 'تغليف وشحن',
                'unit_price' => 1500,
                'quantity' => 1,
                'line_total' => 1500,
            ]);

            SupplierPayment::query()->updateOrCreate(
                [
                    'supplier_id' => $s1->id,
                    'notes' => 'DEMO-PAY-SUP-001',
                ],
                [
                    'amount' => 1500,
                    'currency_code' => 'ILS',
                    'paid_at' => Carbon::now()->subDays(3),
                    'method' => 'نقداً',
                    'recorded_by_user_id' => $user->id,
                ]
            );

            Expense::query()->updateOrCreate(
                [
                    'description' => 'اشتراك أدوات تصميم (تجريبي)',
                    'expense_date' => Carbon::now()->subDays(20)->toDateString(),
                ],
                [
                    'amount' => 450,
                    'currency_code' => 'ILS',
                    'notes' => 'DEMO-EXP-001',
                    'recorded_by_user_id' => $user->id,
                ]
            );

            IncomeEntry::query()->updateOrCreate(
                [
                    'description' => 'عمولة منصة (تجريبي)',
                    'income_date' => Carbon::now()->subDays(12)->toDateString(),
                ],
                [
                    'amount' => 300,
                    'currency_code' => 'ILS',
                    'notes' => 'DEMO-INC-001',
                    'recorded_by_user_id' => $user->id,
                ]
            );
        });
    }

    private function upsertProduct(string $code, string $name, float $cost, float $min, float $sale): Product
    {
        $product = Product::query()->updateOrCreate(
            ['product_code' => $code],
            ['name' => $name, 'description' => null]
        );

        ProductCurrencyPrice::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'currency_code' => 'ILS',
            ],
            [
                'service_cost_price' => $cost,
                'min_sale_price' => $min,
                'sale_price' => $sale,
            ]
        );

        return $product;
    }

    /**
     * @param  array<int, array{product_id: int|null, order: int, title: string, unit: float, qty: float, total: float}>  $rows
     */
    private function replaceInvoiceLines(Invoice $invoice, array $rows): void
    {
        InvoiceLine::query()->where('invoice_id', $invoice->id)->delete();

        foreach ($rows as $row) {
            InvoiceLine::query()->create([
                'invoice_id' => $invoice->id,
                'product_id' => $row['product_id'],
                'line_order' => $row['order'],
                'title' => $row['title'],
                'description' => null,
                'unit_price' => $row['unit'],
                'quantity' => $row['qty'],
                'line_total' => $row['total'],
            ]);
        }
    }
}
