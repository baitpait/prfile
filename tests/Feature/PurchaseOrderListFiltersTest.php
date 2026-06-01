<?php

use App\Livewire\PurchaseOrderList;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

test('purchase order list filters by status and supplier', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    $supplierA = Supplier::factory()->create(['business_name' => 'مطبعة ألف']);
    $supplierB = Supplier::factory()->create(['business_name' => 'ورشة بيتا']);

    PurchaseOrder::create([
        'supplier_id' => $supplierA->id,
        'legacy_po_no' => 'PO-DRAFT-A',
        'document_date' => '2025-03-01',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 100,
        'notes' => null,
        'status' => 'draft',
        'recorded_by_user_id' => null,
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplierB->id,
        'legacy_po_no' => 'PO-ISSUED-B',
        'document_date' => '2025-03-02',
        'due_date' => null,
        'currency_code' => 'USD',
        'discount_amount' => 0,
        'total_amount' => 200,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    Livewire::actingAs($user)
        ->test(PurchaseOrderList::class)
        ->set('filterStatus', 'issued')
        ->assertSee('PO-ISSUED-B')
        ->assertDontSee('PO-DRAFT-A')
        ->set('filterStatus', '')
        ->set('filterSupplierId', (string) $supplierA->id)
        ->assertSee('PO-DRAFT-A')
        ->assertDontSee('PO-ISSUED-B');
});

test('purchase order list filters by document date range', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $supplier = Supplier::factory()->create();

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-EARLY',
        'document_date' => '2025-01-10',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 50,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-LATE',
        'document_date' => '2025-02-10',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 75,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    Livewire::actingAs($user)
        ->test(PurchaseOrderList::class)
        ->set('filterDateFrom', '2025-02-01')
        ->set('filterDateTo', '2025-02-28')
        ->assertSee('PO-LATE')
        ->assertDontSee('PO-EARLY');
});

test('purchase order list supplier search narrows dropdown', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Supplier::factory()->create(['business_name' => 'مورد قابل للبحث']);
    Supplier::factory()->create(['business_name' => 'مورد آخر بعيد']);

    Livewire::actingAs($user)
        ->test(PurchaseOrderList::class)
        ->set('supplierSearch', 'قابل')
        ->assertSee('مورد قابل للبحث')
        ->assertDontSee('مورد آخر بعيد');
});
