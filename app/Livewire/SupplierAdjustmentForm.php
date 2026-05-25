<?php

namespace App\Livewire;

use App\Models\Supplier;
use App\Models\SupplierBalanceAdjustment;
use Livewire\Component;

class SupplierAdjustmentForm extends Component
{
    public Supplier $supplier;

    public ?int $recordId = null;

    public string $amount = '';

    public string $currency_code = 'ILS';

    public string $adjustment_date = '';

    public string $type = SupplierBalanceAdjustment::TYPE_SETTLEMENT_DISCOUNT;

    public string $reason = '';

    public string $notes = '';

    public function mount(Supplier $supplier, ?SupplierBalanceAdjustment $adjustment = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        $this->supplier = $supplier;

        if ($adjustment && $adjustment->exists) {
            abort_unless($adjustment->supplier_id === $supplier->id, 404);
            $this->recordId = $adjustment->id;
            $this->amount = (string) $adjustment->amount;
            $this->currency_code = $adjustment->currency_code ?? 'ILS';
            $this->adjustment_date = $adjustment->adjustment_date?->format('Y-m-d') ?? '';
            $this->type = $adjustment->type;
            $this->reason = $adjustment->reason ?? '';
            $this->notes = $adjustment->notes ?? '';
        } else {
            $this->adjustment_date = now()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'adjustment_date' => 'required|date',
            'type' => 'required|in:'.implode(',', array_keys(SupplierBalanceAdjustment::typeLabels())),
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ], [], [
            'amount' => 'المبلغ',
            'currency_code' => 'العملة',
            'adjustment_date' => 'التاريخ',
            'type' => 'نوع التسوية',
            'reason' => 'السبب',
            'notes' => 'ملاحظات',
        ]);

        $data = [
            'supplier_id' => $this->supplier->id,
            'amount' => $this->amount,
            'currency_code' => strtoupper($this->currency_code),
            'adjustment_date' => $this->adjustment_date,
            'type' => $this->type,
            'reason' => $this->reason ?: null,
            'notes' => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->recordId) {
            SupplierBalanceAdjustment::findOrFail($this->recordId)->update($data);
            $message = 'تم تحديث التسوية';
        } else {
            SupplierBalanceAdjustment::create($data);
            $message = 'تم تسجيل التسوية على الذمة';
        }

        session()->flash('toast', $message);
        $this->redirect(route('suppliers.statement', $this->supplier), navigate: true);
    }

    public function render()
    {
        return view('livewire.supplier-adjustment-form', [
            'typeLabels' => SupplierBalanceAdjustment::typeLabels(),
        ]);
    }
}
