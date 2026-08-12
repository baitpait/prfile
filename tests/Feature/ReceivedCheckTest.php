<?php

use App\Livewire\PaymentForm;
use App\Livewire\ReceivedCheckDueReport;
use App\Livewire\ReceivedCheckList;
use App\Livewire\ReceivedCheckShow;
use App\Livewire\SalaryAdvanceForm;
use App\Livewire\SalaryPaymentForm;
use App\Livewire\SupplierPaymentForm;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\ReceivedCheck;
use App\Models\SalaryAdvance;
use App\Models\SalaryPayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\Finance\PendingReceivedCheckSelectService;
use App\Services\Finance\ReceivedCheckDueReportService;
use App\Services\Finance\ReceivedCheckRegisterService;
use App\Services\Finance\ReceivedCheckStatus;
use App\Services\PurchaseOrderPaymentAllocationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
describe('received checks — intake', function () {
test('check payment creates received check with pending status', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create(['business_name' => 'أحمد للإعلام']);

    Livewire::actingAs($accountant)
        ->test(PaymentForm::class)
        ->set('client_id', (string) $client->id)
        ->set('allocation_mode', 'account')
        ->set('amount', '1500')
        ->set('currency_code', 'ILS')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('payment_method', 'check')
        ->set('check_bank_name', 'بنك القدس')
        ->set('check_drawer_name', 'محمد أحمد (ليس اسم العميل)')
        ->set('check_number', 'CHK-7788')
        ->set('check_due_date', now()->addDays(30)->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('payments.index'));

    $payment = ClientPayment::query()->where('client_id', $client->id)->sole();
    expect($payment->method)->toBe('check');
    expect($payment->bank_reference)->toBe('CHK-7788');

    $check = ReceivedCheck::query()->where('client_payment_id', $payment->id)->sole();
    expect($check->status)->toBe(ReceivedCheckStatus::PENDING);
    expect($check->drawer_name)->toBe('محمد أحمد (ليس اسم العميل)');
    expect((float) $check->amount)->toBe(1500.0);
});

test('check payment can save optional image', function () {
    Storage::fake('public');
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();

    Livewire::actingAs($accountant)
        ->test(PaymentForm::class)
        ->set('client_id', (string) $client->id)
        ->set('amount', '500')
        ->set('currency_code', 'ILS')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('payment_method', 'check')
        ->set('check_bank_name', 'بنك فلسطين')
        ->set('check_drawer_name', 'سارة')
        ->set('check_number', '999')
        ->set('check_due_date', now()->addWeek()->format('Y-m-d'))
        ->set('check_image', UploadedFile::fake()->image('check.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $check = ReceivedCheck::query()->sole();
    expect($check->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($check->image_path);
});

test('cash payment does not create received check', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();

    Livewire::actingAs($accountant)
        ->test(PaymentForm::class)
        ->set('client_id', (string) $client->id)
        ->set('amount', '200')
        ->set('currency_code', 'ILS')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('payment_method', 'cash')
        ->call('save')
        ->assertHasNoErrors();

    expect(ReceivedCheck::query()->count())->toBe(0);
});

test('mark cleared updates check status', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $payment = ClientPayment::query()->create([
        'client_id' => $client->id,
        'amount' => 1000,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'check',
    ]);
    $check = ReceivedCheck::query()->create([
        'client_id' => $client->id,
        'client_payment_id' => $payment->id,
        'bank_name' => 'بنك',
        'drawer_name' => 'X',
        'check_number' => '1',
        'due_date' => now()->addMonth(),
        'amount' => 1000,
        'currency_code' => 'ILS',
        'status' => ReceivedCheckStatus::PENDING,
    ]);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckList::class)
        ->call('markCleared', $check->id);

    expect($check->fresh()->status)->toBe(ReceivedCheckStatus::CLEARED);
    expect($check->fresh()->cleared_at)->not->toBeNull();
});

test('mark not cleared keeps client payment and shows pending reversal', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $payment = ClientPayment::query()->create([
        'client_id' => $client->id,
        'amount' => 800,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'check',
    ]);
    $check = ReceivedCheck::query()->create([
        'client_id' => $client->id,
        'client_payment_id' => $payment->id,
        'bank_name' => 'بنك',
        'drawer_name' => 'Y',
        'check_number' => '2',
        'due_date' => now()->addMonth(),
        'amount' => 800,
        'currency_code' => 'ILS',
        'status' => ReceivedCheckStatus::PENDING,
    ]);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckList::class)
        ->call('markNotCleared', $check->id);

    expect($check->fresh()->status)->toBe(ReceivedCheckStatus::NOT_CLEARED);
    expect(ClientPayment::query()->find($payment->id))->not->toBeNull();
    expect(ClientPayment::withTrashed()->find($payment->id)?->deleted_at)->toBeNull();
});
});

describe('received checks — supplier endorsement', function () {
test('endorse check to supplier on account creates supplier payment and updates status', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create(['business_name' => 'أحمد']);
    $supplier = Supplier::factory()->create(['business_name' => 'مورد الطباعة']);
    $check = makePendingCheck($client, 1200);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check])
        ->call('openEndorseForm')
        ->set('endorse_supplier_id', (string) $supplier->id)
        ->set('endorse_allocation_mode', 'account')
        ->call('endorseToSupplier')
        ->assertHasNoErrors();

    $check->refresh();
    expect($check->status)->toBe(ReceivedCheckStatus::ENDORSED);
    expect($check->endorsed_supplier_id)->toBe($supplier->id);
    expect($check->supplier_payment_id)->not->toBeNull();

    $payment = SupplierPayment::query()->find($check->supplier_payment_id);
    expect($payment)->not->toBeNull();
    expect($payment->supplier_id)->toBe($supplier->id);
    expect((float) $payment->amount)->toBe(1200.0);
    expect($payment->method)->toBe('check');
    expect($payment->purchase_order_id)->toBeNull();
    expect($payment->bank_reference)->toBe('CHK-100');
});

test('endorse check to purchase order links po when amounts match', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $supplier = Supplier::factory()->create();
    $check = makePendingCheck($client, 900);

    $po = PurchaseOrder::query()->create([
        'supplier_id' => $supplier->id,
        'currency_code' => 'ILS',
        'total_amount' => 900,
        'discount_amount' => 0,
        'status' => 'issued',
        'document_date' => now()->format('Y-m-d'),
    ]);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check])
        ->set('endorse_supplier_id', (string) $supplier->id)
        ->set('endorse_allocation_mode', 'purchase_order')
        ->set('endorse_purchase_order_id', (string) $po->id)
        ->call('endorseToSupplier')
        ->assertHasNoErrors();

    $payment = SupplierPayment::query()->where('supplier_id', $supplier->id)->sole();
    expect($payment->purchase_order_id)->toBe($po->id);

    $status = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrder($po->fresh());
    expect($status['status'])->toBe(PurchaseOrderPaymentAllocationService::PAID);
});

test('cannot endorse check twice', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $supplier = Supplier::factory()->create();
    $check = makePendingCheck($client, 500);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check])
        ->set('endorse_supplier_id', (string) $supplier->id)
        ->call('endorseToSupplier');

    expect($check->fresh()->status)->toBe(ReceivedCheckStatus::ENDORSED);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check->fresh()])
        ->call('openEndorseForm')
        ->assertForbidden();
});

test('cleared check cannot be endorsed', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $supplier = Supplier::factory()->create();
    $check = makePendingCheck($client, 300);
    $check->update(['status' => ReceivedCheckStatus::CLEARED, 'cleared_at' => now()]);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check->fresh()])
        ->call('openEndorseForm')
        ->assertForbidden();
});
});

describe('received checks — employee endorsement', function () {
test('endorse check as advance creates salary advance and updates status', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['full_name' => 'سارة الموظفة']);
    $check = makePendingCheck($client, 800);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check])
        ->call('openEndorseEmployeeForm')
        ->set('endorse_employee_id', (string) $employee->id)
        ->set('endorse_employee_mode', 'advance')
        ->call('endorseToEmployee')
        ->assertHasNoErrors();

    $check->refresh();
    expect($check->status)->toBe(ReceivedCheckStatus::ENDORSED);
    expect($check->endorsed_employee_id)->toBe($employee->id);
    expect($check->salary_advance_id)->not->toBeNull();
    expect($check->salary_payment_id)->toBeNull();

    $advance = SalaryAdvance::query()->find($check->salary_advance_id);
    expect($advance)->not->toBeNull();
    expect($advance->employee_id)->toBe($employee->id);
    expect((float) $advance->amount)->toBe(800.0);
    expect($advance->method)->toBe('check');
    expect($advance->bank_reference)->toBe('CHK-100');
});

test('endorse check as salary creates paid salary payment for period', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $employee = Employee::factory()->create();
    $check = makePendingCheck($client, 4500);
    $year = (int) now()->year;
    $month = (int) now()->month;

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check])
        ->set('endorse_employee_id', (string) $employee->id)
        ->set('endorse_employee_mode', 'salary')
        ->set('endorse_period_year', (string) $year)
        ->set('endorse_period_month', (string) $month)
        ->call('endorseToEmployee')
        ->assertHasNoErrors();

    $check->refresh();
    expect($check->status)->toBe(ReceivedCheckStatus::ENDORSED);
    expect($check->salary_payment_id)->not->toBeNull();

    $salary = SalaryPayment::query()->find($check->salary_payment_id);
    expect($salary)->not->toBeNull();
    expect($salary->employee_id)->toBe($employee->id);
    expect($salary->period_year)->toBe($year);
    expect($salary->period_month)->toBe($month);
    expect((float) $salary->net_amount)->toBe(4500.0);
    expect($salary->status)->toBe(SalaryPayment::STATUS_PAID);
    expect($salary->method)->toBe('check');
});

test('supplier endorsed check cannot be endorsed to employee', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $supplier = Supplier::factory()->create();
    $employee = Employee::factory()->create();
    $check = makePendingCheck($client, 600);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check])
        ->set('endorse_supplier_id', (string) $supplier->id)
        ->call('endorseToSupplier');

    expect($check->fresh()->status)->toBe(ReceivedCheckStatus::ENDORSED);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check->fresh()])
        ->call('openEndorseEmployeeForm')
        ->assertForbidden();
});

test('employee endorsed check cannot be endorsed to supplier', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $supplier = Supplier::factory()->create();
    $employee = Employee::factory()->create();
    $check = makePendingCheck($client, 700);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check])
        ->set('endorse_employee_id', (string) $employee->id)
        ->set('endorse_employee_mode', 'advance')
        ->call('endorseToEmployee');

    expect($check->fresh()->status)->toBe(ReceivedCheckStatus::ENDORSED);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check->fresh()])
        ->call('openEndorseForm')
        ->assertForbidden();
});
});

describe('received checks — register', function () {
test('register list filters by currency', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();

    makeCheckForRegister($client, 100, 'ILS');
    makeCheckForRegister($client, 200, 'USD');

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckList::class)
        ->set('currency', 'USD')
        ->assertSee('USD')
        ->assertDontSee('100.00 ILS');
});

test('register shows pending summary by currency', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();

    makeCheckForRegister($client, 500, 'ILS');
    makeCheckForRegister($client, 300, 'ILS');
    makeCheckForRegister($client, 100, 'JOD');

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckList::class)
        ->assertSee('500.00')
        ->assertSee('800.00')
        ->assertSee('100.00');
});

test('check show page renders movement timeline', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $check = makeCheckForRegister($client, 750, 'ILS', ReceivedCheckStatus::NOT_CLEARED);

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check])
        ->assertSee('سجل الحركة')
        ->assertSee('استلام الشيك')
        ->assertSee('لم يُصرف (رجوع)');
});

test('reverse client payment soft deletes payment for bounced check', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $check = makeCheckForRegister($client, 900, 'ILS', ReceivedCheckStatus::NOT_CLEARED);
    $paymentId = $check->client_payment_id;

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check])
        ->call('reverseClientPayment')
        ->assertHasNoErrors();

    expect(ClientPayment::query()->find($paymentId))->toBeNull();
    expect(ClientPayment::withTrashed()->find($paymentId)?->deleted_at)->not->toBeNull();
    expect(app(ReceivedCheckRegisterService::class)->clientPaymentIsReversed($check->fresh()))->toBeTrue();
});

test('reverse client payment blocked when check is not bounced', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $check = makeCheckForRegister($client, 400, 'ILS', ReceivedCheckStatus::PENDING);
    $paymentId = $check->client_payment_id;

    Livewire::actingAs($accountant)
        ->test(ReceivedCheckShow::class, ['receivedCheck' => $check])
        ->call('reverseClientPayment');

    expect(ClientPayment::query()->find($paymentId))->not->toBeNull();
});

test('register service timeline includes payment reversal event', function () {
    $client = Client::factory()->create();
    $check = makeCheckForRegister($client, 600, 'USD', ReceivedCheckStatus::NOT_CLEARED);
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    app(ReceivedCheckRegisterService::class)->reverseClientPayment($check->fresh(), $accountant->id);

    $timeline = app(ReceivedCheckRegisterService::class)->timelineFor($check->fresh());
    $labels = $timeline->pluck('label')->all();

    expect($labels)->toContain('عكس دفعة العميل');
});
});

describe('received checks — payment forms', function () {
test('supplier payment form endorses pending check from register', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $supplier = Supplier::factory()->create();
    $check = makePendingCheck($client, 550);

    Livewire::actingAs($accountant)
        ->test(SupplierPaymentForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('allocation_mode', 'account')
        ->set('payment_method', 'check')
        ->set('check_source', 'register')
        ->set('received_check_id', (string) $check->id)
        ->assertSet('amount', '550.00')
        ->set('paid_at', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('supplier-payments.index'));

    $check->refresh();
    expect($check->status)->toBe(ReceivedCheckStatus::ENDORSED);
    expect($check->supplier_payment_id)->not->toBeNull();

    $payment = SupplierPayment::query()->find($check->supplier_payment_id);
    expect($payment)->not->toBeNull();
    expect($payment->supplier_id)->toBe($supplier->id);
    expect((float) $payment->amount)->toBe(550.0);
    expect($payment->method)->toBe('check');
});

test('supplier payment form manual check path still creates plain payment', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $supplier = Supplier::factory()->create();

    Livewire::actingAs($accountant)
        ->test(SupplierPaymentForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('allocation_mode', 'account')
        ->set('amount', '320')
        ->set('currency_code', 'ILS')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('payment_method', 'check')
        ->set('check_source', 'manual')
        ->set('bank_reference', 'EXT-99')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('supplier-payments.index'));

    $payment = SupplierPayment::query()->where('supplier_id', $supplier->id)->sole();
    expect((float) $payment->amount)->toBe(320.0);
    expect($payment->bank_reference)->toBe('EXT-99');
    expect(ReceivedCheck::query()->where('status', ReceivedCheckStatus::ENDORSED)->count())->toBe(0);
});

test('salary payment form endorses pending check when net matches', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $employee = Employee::factory()->create();
    $check = makePendingCheck($client, 4200);
    $year = (int) now()->year;
    $month = (int) now()->month;

    Livewire::actingAs($accountant)
        ->test(SalaryPaymentForm::class)
        ->set('employee_id', (string) $employee->id)
        ->set('period_year', (string) $year)
        ->set('period_month', (string) $month)
        ->set('status', SalaryPayment::STATUS_PAID)
        ->set('method', 'check')
        ->set('check_source', 'register')
        ->set('received_check_id', (string) $check->id)
        ->assertSet('base_amount', '4200.00')
        ->set('paid_at', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('salary-payments.index'));

    $check->refresh();
    expect($check->status)->toBe(ReceivedCheckStatus::ENDORSED);
    expect($check->salary_payment_id)->not->toBeNull();

    $salary = SalaryPayment::query()->find($check->salary_payment_id);
    expect($salary)->not->toBeNull();
    expect($salary->employee_id)->toBe($employee->id);
    expect((float) $salary->net_amount)->toBe(4200.0);
    expect($salary->status)->toBe(SalaryPayment::STATUS_PAID);
});

test('salary advance form endorses pending check from register', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $employee = Employee::factory()->create();
    $check = makePendingCheck($client, 950);

    Livewire::actingAs($accountant)
        ->test(SalaryAdvanceForm::class)
        ->set('employee_id', (string) $employee->id)
        ->set('method', 'check')
        ->set('check_source', 'register')
        ->set('received_check_id', (string) $check->id)
        ->assertSet('amount', '950.00')
        ->set('paid_at', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('salary-advances.index'));

    $check->refresh();
    expect($check->status)->toBe(ReceivedCheckStatus::ENDORSED);
    expect($check->salary_advance_id)->not->toBeNull();

    $advance = SalaryAdvance::query()->find($check->salary_advance_id);
    expect($advance)->not->toBeNull();
    expect($advance->employee_id)->toBe($employee->id);
    expect((float) $advance->amount)->toBe(950.0);
    expect($advance->method)->toBe('check');
});

test('salary payment form rejects register check when net differs', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $employee = Employee::factory()->create();
    $check = makePendingCheck($client, 1000);

    Livewire::actingAs($accountant)
        ->test(SalaryPaymentForm::class)
        ->set('employee_id', (string) $employee->id)
        ->set('period_year', (string) now()->year)
        ->set('period_month', (string) now()->month)
        ->set('status', SalaryPayment::STATUS_PAID)
        ->set('method', 'check')
        ->set('check_source', 'register')
        ->set('received_check_id', (string) $check->id)
        ->set('deduction_amount', '100')
        ->set('paid_at', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors(['received_check_id']);

    expect($check->fresh()->status)->toBe(ReceivedCheckStatus::PENDING);
});
});

describe('received checks — payment form UX', function () {
test('pending check select service filters by exact amount', function () {
    $client = Client::factory()->create();
    $low = makePendingCheck($client, 750);
    makePendingCheck($client, 1200);

    $service = app(\App\Services\Finance\PendingReceivedCheckSelectService::class);

    expect($service->pendingFor('ILS', null))->toHaveCount(2);
    expect($service->pendingFor('ILS', 750))->toHaveCount(1)
        ->and($service->pendingFor('ILS', 750)->first()->id)->toBe($low->id);
});

test('supplier payment form exposes amount filter flag when amount entered', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $supplier = Supplier::factory()->create();

    $component = Livewire::actingAs($accountant)
        ->test(SupplierPaymentForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('payment_method', 'check')
        ->set('check_source', 'register')
        ->set('amount', '750');

    expect($component->viewData('amountFilter'))->toBeTrue();
    expect($component->instance()->canSelectPendingCheck())->toBeTrue();
});

test('supplier payment form clears selected check when amount changes', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $supplier = Supplier::factory()->create();
    $check = makePendingCheck($client, 500);

    Livewire::actingAs($accountant)
        ->test(SupplierPaymentForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('payment_method', 'check')
        ->set('check_source', 'register')
        ->set('received_check_id', (string) $check->id)
        ->assertSet('received_check_id', (string) $check->id)
        ->set('amount', '999')
        ->assertSet('received_check_id', '');
});

test('salary form blocks suggest deduction when register check path is active', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $employee = Employee::factory()->create();
    makePendingCheck($client, 3000);

    SalaryAdvance::query()->create([
        'employee_id' => $employee->id,
        'amount' => 200,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'cash',
        'status' => SalaryAdvance::STATUS_PAID,
        'recorded_by_user_id' => $accountant->id,
    ]);

    Livewire::actingAs($accountant)
        ->test(SalaryPaymentForm::class)
        ->set('employee_id', (string) $employee->id)
        ->set('status', 'paid')
        ->set('method', 'check')
        ->set('check_source', 'register')
        ->call('suggestDeductionFromAdvances')
        ->assertHasErrors(['deduction_amount'])
        ->assertSet('deduction_amount', '0');
});

test('edit supplier payment with check shows register unavailable notice', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $supplier = Supplier::factory()->create();
    $payment = SupplierPayment::query()->create([
        'supplier_id' => $supplier->id,
        'amount' => 400,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'check',
        'bank_reference' => 'OLD-1',
        'recorded_by_user_id' => $accountant->id,
    ]);

    Livewire::actingAs($accountant)
        ->test(SupplierPaymentForm::class, ['supplierPayment' => $payment])
        ->assertSee('صندوق الشيكات')
        ->assertSee('إنشاء');
});
});

describe('received checks — due report', function () {
    test('due report service buckets pending checks by due date', function () {
        $client = Client::factory()->create();
        $today = now()->startOfDay();
        $svc = app(ReceivedCheckDueReportService::class);

        makePendingCheck($client, 100, 'ILS', ReceivedCheckStatus::PENDING, $today->copy()->subDays(3), 'D-OVER');
        makePendingCheck($client, 200, 'ILS', ReceivedCheckStatus::PENDING, $today->copy()->addDays(2), 'D-7');
        makePendingCheck($client, 300, 'ILS', ReceivedCheckStatus::PENDING, $today->copy()->addDays(20), 'D-30');

        expect($svc->rows(null, ReceivedCheckDueReportService::BUCKET_OVERDUE))->toHaveCount(1);
        expect($svc->rows(null, ReceivedCheckDueReportService::BUCKET_DUE_0_7))->toHaveCount(1);
        expect($svc->rows(null, ReceivedCheckDueReportService::BUCKET_DUE_8_30))->toHaveCount(1);
    });

    test('due report page loads for accountant', function () {
        $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
        $client = Client::factory()->create();
        makePendingCheck($client, 500, 'ILS', ReceivedCheckStatus::PENDING, now()->addDays(2), 'D-PAGE');

        Livewire::actingAs($accountant)
            ->test(ReceivedCheckDueReport::class)
            ->assertSee('تقرير استحقاق الشيكات')
            ->assertSee('500.00');
    });

    test('viewer cannot export due report csv', function () {
        $viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

        Livewire::actingAs($viewer)
            ->test(ReceivedCheckDueReport::class)
            ->call('exportCsv')
            ->assertForbidden();
    });

    test('accountant can export due report csv', function () {
        $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
        $client = Client::factory()->create();
        makePendingCheck($client, 888, 'ILS', ReceivedCheckStatus::PENDING, now()->addDays(5), 'D-CSV');

        Livewire::actingAs($accountant)
            ->test(ReceivedCheckDueReport::class)
            ->call('exportCsv')
            ->assertOk();
    });

    test('accountant can download due report pdf', function () {
        $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
        $client = Client::factory()->create();
        makePendingCheck($client, 777, 'ILS', ReceivedCheckStatus::PENDING, now()->addDays(3), 'D-PDF');

        $this->actingAs($accountant)
            ->get(route('received-checks.due-report.pdf', ['currency' => 'ILS']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    test('due report pdf template includes pending check row', function () {
        $client = Client::factory()->create(['business_name' => 'عميل PDF']);
        makePendingCheck($client, 777, 'ILS', ReceivedCheckStatus::PENDING, now()->addDays(3), 'D-PDF-TPL');

        $service = app(ReceivedCheckDueReportService::class);
        $rows = $service->rows('ILS');
        $html = view('pdf.received-check-due-report', [
            'rows' => $rows,
            'totalsByCurrency' => $service->totalsByCurrency(),
            'filterLabels' => $service->describeFilters('ILS'),
            'bucketOptions' => $service->bucketLabels(),
            'service' => $service,
            'companyName' => 'Profile Media Production',
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->render();

        expect($html)->toContain('D-PDF-TPL');
        expect($html)->toContain('777.00');
        expect($html)->toContain('عميل PDF');
    });

    test('viewer cannot download due report pdf', function () {
        $viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

        $this->actingAs($viewer)
            ->get(route('received-checks.due-report.pdf'))
            ->assertForbidden();
    });

    test('sidebar includes due report link for active users', function () {
        $viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

        $this->actingAs($viewer)
            ->get(route('clients.index'))
            ->assertOk()
            ->assertSee('استحقاق الشيكات');
    });
});
