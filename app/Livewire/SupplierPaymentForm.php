<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPendingReceivedCheckSelection;
use App\Models\PurchaseOrder;
use App\Models\ReceivedCheck;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\Finance\PaymentMethod;
use App\Services\Finance\ReceivedCheckEndorsementService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SupplierPaymentForm extends Component
{
    use WithPendingReceivedCheckSelection;
    public ?int $recordId = null;

    public string $supplier_id = '';

    /** account = عموم الحساب | purchase_order = على أمر شراء */
    public string $allocation_mode = 'account';

    public string $purchase_order_id = '';

    public string $amount = '';

    public string $currency_code = 'ILS';

    public string $paid_at = '';

    public string $payment_method = 'cash';

    public string $bank_reference = '';

    public string $notes = '';

    public function mount(?SupplierPayment $supplierPayment = null): void
    {
        if ($supplierPayment && $supplierPayment->exists) {
            $this->authorize('update', $supplierPayment);
        } else {
            $this->authorize('create', SupplierPayment::class);
        }

        if ($supplierPayment && $supplierPayment->exists) {
            $supplierPayment->loadMissing('purchaseOrder');
            $this->recordId = $supplierPayment->id;
            $this->supplier_id = (string) $supplierPayment->supplier_id;
            $this->amount = (string) $supplierPayment->amount;
            $this->currency_code = $supplierPayment->currency_code ?? 'ILS';
            $this->paid_at = $supplierPayment->paid_at?->format('Y-m-d') ?? '';
            $this->payment_method = PaymentMethod::normalize($supplierPayment->method);
            $this->bank_reference = $supplierPayment->bank_reference ?? '';
            $this->notes = $supplierPayment->notes ?? '';
            if ($supplierPayment->purchase_order_id) {
                $this->allocation_mode = 'purchase_order';
                $this->purchase_order_id = (string) $supplierPayment->purchase_order_id;
            }
        } else {
            $this->paid_at = now()->format('Y-m-d');
            $prefillSupplierId = request()->integer('supplier');
            if ($prefillSupplierId > 0 && Supplier::query()->whereKey($prefillSupplierId)->exists()) {
                $this->supplier_id = (string) $prefillSupplierId;
            }
        }
    }

    public function updatedAllocationMode(string $value): void
    {
        if ($value !== 'purchase_order') {
            $this->purchase_order_id = '';
        }
    }

    public function updatedSupplierId(): void
    {
        $this->purchase_order_id = '';
        if ($this->allocation_mode === 'purchase_order') {
            $this->amount = '';
        }
    }

    public function updatedCurrencyCode(): void
    {
        $this->purchase_order_id = '';
        if ($this->allocation_mode === 'purchase_order') {
            $this->amount = '';
        }
        if ($this->check_source === 'register') {
            $this->received_check_id = '';
        }
    }

    public function updatedAmount(): void
    {
        $this->reconcileRegisterCheckWithFilterAmount();
    }

    protected function pendingCheckFilterAmount(): ?float
    {
        if ($this->amount === '' || ! is_numeric($this->amount)) {
            return null;
        }

        return (float) $this->amount;
    }

    protected function applyPendingCheckToForm(ReceivedCheck $check): void
    {
        $this->currency_code = $check->currency_code ?? 'ILS';
        $this->amount = number_format((float) $check->amount, 2, '.', '');
        $this->bank_reference = $check->check_number;
        $this->payment_method = PaymentMethod::CHECK;
        $this->check_source = 'register';
    }

    /**
     * Business Purpose: When linking to a PO, lock amount to the full PO total (no split).
     */
    public function updatedPurchaseOrderId(): void
    {
        if ($this->allocation_mode !== 'purchase_order' || $this->purchase_order_id === '') {
            return;
        }

        $po = $this->findSelectablePurchaseOrder((int) $this->purchase_order_id);
        if (! $po) {
            $this->purchase_order_id = '';
            $this->amount = '';

            return;
        }

        $this->currency_code = $po->currency_code ?? 'ILS';
        $this->amount = number_format((float) $po->total_amount, 2, '.', '');
        if ($this->check_source === 'register') {
            $this->reconcileRegisterCheckWithFilterAmount();
        }
    }

    public function save(): void
    {
        $usingRegisterCheck = $this->canSelectPendingCheck()
            && $this->check_source === 'register';

        $rules = [
            'supplier_id' => 'required|exists:suppliers,id',
            'allocation_mode' => 'required|in:account,purchase_order',
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'paid_at' => 'required|date',
            'payment_method' => PaymentMethod::validationRule(),
            'purchase_order_id' => 'nullable',
        ];

        if ($this->allocation_mode === 'purchase_order') {
            $rules['purchase_order_id'] = [
                'required',
                'integer',
                Rule::exists('purchase_orders', 'id')->where(function ($query) {
                    $query->where('supplier_id', (int) $this->supplier_id)
                        ->where('currency_code', $this->currency_code)
                        ->where('status', 'issued')
                        ->whereNull('deleted_at');
                }),
            ];
        }

        $this->validate($rules, [], [
            'supplier_id' => 'المورد',
            'allocation_mode' => 'نوع الربط',
            'purchase_order_id' => 'أمر الشراء',
            'amount' => 'المبلغ',
            'currency_code' => 'العملة',
            'paid_at' => 'تاريخ الدفع',
            'payment_method' => 'طريقة الدفع',
        ]);

        if ($usingRegisterCheck) {
            $this->validate([
                'received_check_id' => 'required|integer|exists:received_checks,id',
            ], [], [
                'received_check_id' => 'الشيك',
            ]);
        }

        $purchaseOrderId = null;
        if ($this->allocation_mode === 'purchase_order') {
            $po = $this->findSelectablePurchaseOrder((int) $this->purchase_order_id);
            if (! $po) {
                $this->addError('purchase_order_id', 'أمر الشراء غير متاح للربط (صادر، نفس العملة، وغير مربوط بدفعة أخرى).');

                return;
            }

            $poTotal = round((float) $po->total_amount, 2);
            $paymentAmount = round((float) $this->amount, 2);
            if (abs($poTotal - $paymentAmount) > 0.009) {
                $this->addError('amount', 'مبلغ الدفعة يجب أن يساوي إجمالي أمر الشراء ('.number_format($poTotal, 2).').');

                return;
            }

            $this->amount = number_format($poTotal, 2, '.', '');
            $purchaseOrderId = $po->id;
        }

        if ($usingRegisterCheck) {
            $check = $this->selectedPendingCheck();
            if (! $check) {
                $this->addError('received_check_id', 'الشيك غير متاح للتظهير.');

                return;
            }

            if ($check->currency_code !== $this->currency_code) {
                $this->addError('received_check_id', 'عملة الشيك لا تطابق عملة الدفعة.');

                return;
            }

            if (abs(round((float) $check->amount, 2) - round((float) $this->amount, 2)) > 0.009) {
                $this->addError('amount', 'مبلغ الدفعة يجب أن يساوي مبلغ الشيك المختار.');

                return;
            }

            try {
                app(ReceivedCheckEndorsementService::class)->endorseToSupplier(
                    $check->fresh(),
                    (int) $this->supplier_id,
                    $purchaseOrderId,
                    auth()->id(),
                );
            } catch (\InvalidArgumentException $e) {
                $this->addError('received_check_id', $e->getMessage());

                return;
            }

            session()->flash('toast', 'تم تسجيل الدفعة وتظهير الشيك للمورد');
            $this->redirect(route('supplier-payments.index'), navigate: true);

            return;
        }

        $data = [
            'supplier_id' => $this->supplier_id,
            'purchase_order_id' => $purchaseOrderId,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'paid_at' => $this->paid_at,
            'method' => $this->payment_method,
            'bank_reference' => $this->bank_reference ?: null,
            'notes' => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->recordId) {
            SupplierPayment::findOrFail($this->recordId)->update($data);
        } else {
            SupplierPayment::create($data);
        }

        session()->flash('toast', $this->recordId ? 'تم تحديث الدفعة' : 'تم تسجيل الدفعة بنجاح');
        $this->redirect(route('supplier-payments.index'), navigate: true);
    }

    public function render()
    {
        $pendingChecks = $this->pendingChecksForSelect(
            $this->currency_code !== '' ? $this->currency_code : null,
        );

        $filterAmount = $this->resolvePendingCheckFilterAmount();

        return view('livewire.supplier-payment-form', [
            'suppliers' => Supplier::orderBy('business_name')->orderBy('first_name')->get(),
            'openPurchaseOrders' => $this->openPurchaseOrdersForSelect(),
            'pendingChecks' => $pendingChecks,
            'selectedCheck' => $this->selectedPendingCheck(),
            'canSelectPendingCheck' => $this->canSelectPendingCheck(),
            'isCheckPayment' => $this->isCheckPaymentMethod(),
            'currencyFilter' => $this->currency_code !== '',
            'amountFilter' => $filterAmount !== null,
        ]);
    }

    /**
     * @return Collection<int, PurchaseOrder>
     */
    protected function openPurchaseOrdersForSelect(): Collection
    {
        if ($this->supplier_id === '' || $this->allocation_mode !== 'purchase_order') {
            return collect();
        }

        $linkedIds = SupplierPayment::query()
            ->whereNotNull('purchase_order_id')
            ->when($this->recordId, fn ($q) => $q->where('id', '!=', $this->recordId))
            ->pluck('purchase_order_id');

        return PurchaseOrder::query()
            ->where('supplier_id', (int) $this->supplier_id)
            ->where('currency_code', $this->currency_code)
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereNotIn('id', $linkedIds)
            ->orderByDesc('document_date')
            ->orderByDesc('id')
            ->get();
    }

    protected function findSelectablePurchaseOrder(int $purchaseOrderId): ?PurchaseOrder
    {
        return $this->openPurchaseOrdersForSelect()->firstWhere('id', $purchaseOrderId);
    }
}
