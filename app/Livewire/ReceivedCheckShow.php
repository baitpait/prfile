<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\ReceivedCheck;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\Finance\ReceivedCheckEndorsementService;
use App\Services\Finance\ReceivedCheckRegisterService;
use App\Services\Finance\ReceivedCheckStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ReceivedCheckShow extends Component
{
    use AuthorizesRequests;

    public ReceivedCheck $check;

    public bool $showEndorseForm = false;

    public bool $showEndorseEmployeeForm = false;

    public string $endorse_supplier_id = '';

    public string $endorse_allocation_mode = 'account';

    public string $endorse_purchase_order_id = '';

    public string $endorse_employee_id = '';

    public string $endorse_employee_mode = 'advance';

    public string $endorse_period_year = '';

    public string $endorse_period_month = '';

    public function mount(ReceivedCheck $receivedCheck): void
    {
        $this->authorize('view', $receivedCheck);
        $this->check = $receivedCheck->load([
            'client',
            'clientPayment' => fn ($q) => $q->withTrashed(),
            'recordedBy',
            'endorsedSupplier',
            'supplierPayment',
            'endorsedEmployee',
            'salaryPayment',
            'salaryAdvance',
        ]);

        $this->endorse_period_year = (string) now()->year;
        $this->endorse_period_month = (string) now()->month;
    }

    public function openEndorseForm(): void
    {
        $this->authorize('endorse', $this->check);
        $this->showEndorseEmployeeForm = false;
        $this->cancelEndorseEmployeeForm();
        $this->showEndorseForm = true;
    }

    public function openEndorseEmployeeForm(): void
    {
        $this->authorize('endorse', $this->check);
        $this->showEndorseForm = false;
        $this->cancelEndorseForm();
        $this->showEndorseEmployeeForm = true;
    }

    public function cancelEndorseForm(): void
    {
        $this->showEndorseForm = false;
        $this->endorse_supplier_id = '';
        $this->endorse_allocation_mode = 'account';
        $this->endorse_purchase_order_id = '';
    }

    public function cancelEndorseEmployeeForm(): void
    {
        $this->showEndorseEmployeeForm = false;
        $this->endorse_employee_id = '';
        $this->endorse_employee_mode = 'advance';
        $this->endorse_period_year = (string) now()->year;
        $this->endorse_period_month = (string) now()->month;
    }

    public function updatedEndorseSupplierId(): void
    {
        $this->endorse_purchase_order_id = '';
    }

    /**
     * Business Purpose: Pass the physical check to a supplier — creates supplier payment for the same amount.
     */
    public function endorseToSupplier(): void
    {
        $this->authorize('endorse', $this->check);

        $rules = [
            'endorse_supplier_id' => 'required|exists:suppliers,id',
            'endorse_allocation_mode' => 'required|in:account,purchase_order',
        ];

        if ($this->endorse_allocation_mode === 'purchase_order') {
            $rules['endorse_purchase_order_id'] = [
                'required',
                'integer',
                Rule::exists('purchase_orders', 'id')->where(function ($query) {
                    $query->where('supplier_id', (int) $this->endorse_supplier_id)
                        ->where('currency_code', $this->check->currency_code)
                        ->where('status', 'issued')
                        ->whereNull('deleted_at');
                }),
            ];
        }

        $this->validate($rules, [], [
            'endorse_supplier_id' => 'المورد',
            'endorse_allocation_mode' => 'نوع الربط',
            'endorse_purchase_order_id' => 'أمر الشراء',
        ]);

        $purchaseOrderId = $this->endorse_allocation_mode === 'purchase_order'
            ? (int) $this->endorse_purchase_order_id
            : null;

        try {
            $payment = app(ReceivedCheckEndorsementService::class)->endorseToSupplier(
                $this->check->fresh(),
                (int) $this->endorse_supplier_id,
                $purchaseOrderId,
                auth()->id(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->addError('endorse_supplier_id', $e->getMessage());

            return;
        }

        $this->check->refresh()->load(['endorsedSupplier', 'supplierPayment']);
        $this->showEndorseForm = false;
        $this->cancelEndorseForm();

        session()->flash('toast', 'تم تظهير الشيك للمورد وإنشاء دفعة #'.$payment->id);
        $this->dispatch('toast', message: 'تم تظهير الشيك للمورد.');
    }

    /**
     * Business Purpose: Pass the physical check to an employee as salary or advance.
     */
    public function endorseToEmployee(): void
    {
        $this->authorize('endorse', $this->check);

        $rules = [
            'endorse_employee_id' => 'required|exists:employees,id',
            'endorse_employee_mode' => 'required|in:advance,salary',
        ];

        if ($this->endorse_employee_mode === 'salary') {
            $rules['endorse_period_year'] = 'required|integer|min:2000|max:2100';
            $rules['endorse_period_month'] = 'required|integer|min:1|max:12';
        }

        $this->validate($rules, [], [
            'endorse_employee_id' => 'الموظف',
            'endorse_employee_mode' => 'نوع الدفع',
            'endorse_period_year' => 'سنة الراتب',
            'endorse_period_month' => 'شهر الراتب',
        ]);

        $periodYear = $this->endorse_employee_mode === 'salary'
            ? (int) $this->endorse_period_year
            : null;
        $periodMonth = $this->endorse_employee_mode === 'salary'
            ? (int) $this->endorse_period_month
            : null;

        try {
            $record = app(ReceivedCheckEndorsementService::class)->endorseToEmployee(
                $this->check->fresh(),
                (int) $this->endorse_employee_id,
                $this->endorse_employee_mode,
                $periodYear,
                $periodMonth,
                auth()->id(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->addError('endorse_employee_id', $e->getMessage());

            return;
        }

        $this->check->refresh()->load(['endorsedEmployee', 'salaryPayment', 'salaryAdvance']);
        $this->showEndorseEmployeeForm = false;
        $this->cancelEndorseEmployeeForm();

        $label = $this->endorse_employee_mode === 'advance' ? 'سلفة' : 'راتب';
        session()->flash('toast', "تم تظهير الشيك للموظف ({$label}) — سجل #{$record->id}");
        $this->dispatch('toast', message: 'تم تظهير الشيك للموظف.');
    }

    public function markCleared(): void
    {
        $this->authorize('updateStatus', $this->check);

        if (! $this->check->isPending()) {
            return;
        }

        $this->check->update([
            'status' => ReceivedCheckStatus::CLEARED,
            'cleared_at' => now(),
            'not_cleared_at' => null,
        ]);

        $this->check->refresh();
        $this->dispatch('toast', message: 'تم تسجيل الشيك كـ «صُرف».');
    }

    public function markNotCleared(): void
    {
        $this->authorize('updateStatus', $this->check);

        if (! $this->check->isPending()) {
            return;
        }

        $this->check->update([
            'status' => ReceivedCheckStatus::NOT_CLEARED,
            'not_cleared_at' => now(),
            'cleared_at' => null,
        ]);

        $this->check->refresh();
        session()->flash(
            'toast',
            'تم تسجيل «لم يُصرف». يُرجى إلغاء أو عكس دفعة العميل يدوياً من قائمة الدفعات.'
        );
        $this->dispatch('toast', message: 'تم تسجيل «لم يُصرف» — يمكنك عكس دفعة العميل أدناه.');
    }

    /**
     * Business Purpose: Reverse linked client payment after check bounce — restores client balance.
     */
    public function reverseClientPayment(): void
    {
        $this->authorize('reverseClientPayment', $this->check);

        try {
            app(ReceivedCheckRegisterService::class)->reverseClientPayment(
                $this->check->fresh(),
                auth()->id(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        }

        $this->check->refresh()->load([
            'client',
            'clientPayment' => fn ($q) => $q->withTrashed(),
            'recordedBy',
            'endorsedSupplier',
            'supplierPayment',
            'endorsedEmployee',
            'salaryPayment',
            'salaryAdvance',
        ]);

        $this->dispatch('toast', message: 'تم عكس دفعة العميل.');
    }

    public function render()
    {
        $register = app(ReceivedCheckRegisterService::class);

        return view('livewire.received-check-show', [
            'suppliers' => Supplier::query()->orderBy('business_name')->orderBy('first_name')->get(),
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->get(),
            'openPurchaseOrders' => $this->openPurchaseOrdersForEndorse(),
            'timeline' => $register->timelineFor($this->check),
            'paymentReversed' => $register->clientPaymentIsReversed($this->check),
        ]);
    }

    /**
     * @return Collection<int, PurchaseOrder>
     */
    protected function openPurchaseOrdersForEndorse(): Collection
    {
        if ($this->endorse_supplier_id === '' || $this->endorse_allocation_mode !== 'purchase_order') {
            return collect();
        }

        $linkedPoIds = SupplierPayment::query()
            ->whereNotNull('purchase_order_id')
            ->whereNull('deleted_at')
            ->pluck('purchase_order_id');

        $checkAmount = round((float) $this->check->amount, 2);

        return PurchaseOrder::query()
            ->where('supplier_id', (int) $this->endorse_supplier_id)
            ->where('currency_code', $this->check->currency_code)
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereNotIn('id', $linkedPoIds)
            ->whereRaw('ROUND(total_amount, 2) = ?', [$checkAmount])
            ->orderByDesc('document_date')
            ->orderByDesc('id')
            ->get();
    }
}
