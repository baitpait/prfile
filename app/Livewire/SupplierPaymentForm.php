<?php

namespace App\Livewire;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use Livewire\Component;

class SupplierPaymentForm extends Component
{
    public ?int $recordId = null;

    public string $supplier_id = '';

    public string $amount = '';

    public string $currency_code = 'ILS';

    public string $paid_at = '';

    public string $payment_method = 'cash';

    public string $bank_reference = '';

    public string $notes = '';

    public function mount(?SupplierPayment $supplierPayment = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        if ($supplierPayment && $supplierPayment->exists) {
            $this->recordId = $supplierPayment->id;
            $this->supplier_id = (string) $supplierPayment->supplier_id;
            $this->amount = (string) $supplierPayment->amount;
            $this->currency_code = $supplierPayment->currency_code ?? 'ILS';
            $this->paid_at = $supplierPayment->paid_at?->format('Y-m-d') ?? '';
            $this->payment_method = $supplierPayment->method ?? 'cash';
            $this->bank_reference = $supplierPayment->bank_reference ?? '';
            $this->notes = $supplierPayment->notes ?? '';
        } else {
            $this->paid_at = now()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'paid_at' => 'required|date',
            'payment_method' => 'required|in:cash,bank,check,transfer',
        ], [], [
            'supplier_id' => 'المورد',
            'amount' => 'المبلغ',
            'currency_code' => 'العملة',
            'paid_at' => 'تاريخ الدفع',
            'payment_method' => 'طريقة الدفع',
        ]);

        $data = [
            'supplier_id' => $this->supplier_id,
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
        return view('livewire.supplier-payment-form', [
            'suppliers' => Supplier::orderBy('business_name')->orderBy('first_name')->get(),
        ]);
    }
}
