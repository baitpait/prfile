<?php

namespace App\Livewire;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PurchaseOrderForm extends Component
{
    public ?int $purchaseOrderId = null;

    public string $supplier_id = '';

    public string $legacy_po_no = '';

    public string $document_date = '';

    public string $due_date = '';

    public string $currency_code = 'ILS';

    public string $total_amount = '0';

    public string $discount_amount = '0';

    public string $status = 'draft';

    public string $notes = '';

    /** @var array<int, array{title:string, description:string, unit_price:string, quantity:string, line_total:string}> */
    public array $lines = [];

    public function mount(?PurchaseOrder $purchaseOrder = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        if ($purchaseOrder && $purchaseOrder->exists) {
            Gate::authorize('update', $purchaseOrder);
            $purchaseOrder->load('lines');
            $this->purchaseOrderId = $purchaseOrder->id;
            $this->supplier_id = (string) $purchaseOrder->supplier_id;
            $this->legacy_po_no = $purchaseOrder->legacy_po_no ?? '';
            $this->document_date = $purchaseOrder->document_date?->format('Y-m-d') ?? '';
            $this->due_date = $purchaseOrder->due_date?->format('Y-m-d') ?? '';
            $this->currency_code = $purchaseOrder->currency_code ?? 'ILS';
            $this->total_amount = (string) $purchaseOrder->total_amount;
            $this->discount_amount = (string) ($purchaseOrder->discount_amount ?? 0);
            $this->status = $purchaseOrder->status ?? 'draft';
            $this->notes = $purchaseOrder->notes ?? '';
            $this->lines = $purchaseOrder->lines->map(fn ($l) => [
                'title' => $l->title ?? '',
                'description' => $l->description ?? '',
                'unit_price' => (string) $l->unit_price,
                'quantity' => (string) $l->quantity,
                'line_total' => (string) $l->line_total,
            ])->toArray();
        } else {
            Gate::authorize('create', PurchaseOrder::class);
            $this->document_date = now()->format('Y-m-d');
        }

        if (count($this->lines) === 0) {
            $this->addLine();
        }
    }

    public function updatedLines(mixed $value, string $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) === 2 && in_array($parts[1], ['unit_price', 'quantity'], true)) {
            $i = (int) $parts[0];
            $price = (float) ($this->lines[$i]['unit_price'] ?? 0);
            $qty = (float) ($this->lines[$i]['quantity'] ?? 1);
            $this->lines[$i]['line_total'] = (string) round($price * $qty, 4);
            $this->recalcTotal();
        }
    }

    public function updatedDiscountAmount(): void
    {
        $this->recalcTotal();
    }

    private function recalcTotal(): void
    {
        $subtotal = collect($this->lines)
            ->filter(fn ($l) => trim((string) ($l['title'] ?? '')) !== '')
            ->sum(fn ($l) => (float) ($l['line_total'] ?? 0));
        $net = max(0, $subtotal - (float) ($this->discount_amount ?? 0));
        $this->total_amount = (string) round($net, 2);
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'title' => '',
            'description' => '',
            'unit_price' => '',
            'quantity' => '1',
            'line_total' => '0',
        ];
    }

    public function removeLine(int $index): void
    {
        array_splice($this->lines, $index, 1);
        $this->lines = array_values($this->lines);
        $this->recalcTotal();
    }

    private function syncLineTotalsFromInputs(): void
    {
        foreach ($this->lines as $i => $line) {
            $p = (float) ($line['unit_price'] ?? 0);
            $q = (float) ($line['quantity'] ?? 0);
            $this->lines[$i]['line_total'] = (string) round($p * $q, 4);
        }
        $this->recalcTotal();
    }

    public function save(): void
    {
        if ($this->purchaseOrderId) {
            Gate::authorize('update', PurchaseOrder::findOrFail($this->purchaseOrderId));
        } else {
            Gate::authorize('create', PurchaseOrder::class);
        }

        $this->syncLineTotalsFromInputs();

        $this->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'currency_code' => 'required|string|size:3',
            'status' => 'required|in:draft,issued,void',
            'legacy_po_no' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('purchase_orders', 'legacy_po_no')->ignore($this->purchaseOrderId),
            ],
            'lines' => 'array',
            'lines.*.title' => 'required_with:lines|string|max:500',
            'lines.*.quantity' => 'required_with:lines|numeric|min:0',
            'lines.*.unit_price' => 'required_with:lines|numeric|min:0',
        ], [
            'lines.*.title.required_with' => 'اسم البند مطلوب',
            'lines.*.quantity.required_with' => 'الكمية مطلوبة',
            'lines.*.unit_price.required_with' => 'السعر مطلوب',
        ], [
            'supplier_id' => 'المورد',
            'document_date' => 'تاريخ المستند',
            'due_date' => 'تاريخ الاستحقاق',
            'currency_code' => 'العملة',
            'status' => 'الحالة',
            'legacy_po_no' => 'رقم المستند',
        ]);

        $titledLines = array_values(array_filter($this->lines, fn ($l) => trim((string) ($l['title'] ?? '')) !== ''));
        if ($titledLines === []) {
            $this->addError('lines', 'أضف بنداً واحداً على الأقل.');

            return;
        }

        $subtotal = collect($titledLines)->sum(fn ($l) => (float) ($l['line_total'] ?? 0));
        $this->total_amount = (string) max(0, round($subtotal - (float) ($this->discount_amount ?? 0), 2));

        if (empty($this->total_amount) || (float) $this->total_amount <= 0) {
            $this->addError('total_amount', 'المبلغ الإجمالي مطلوب ويجب أن يكون أكبر من صفر');

            return;
        }

        $data = [
            'supplier_id' => $this->supplier_id,
            'legacy_po_no' => $this->legacy_po_no !== '' ? $this->legacy_po_no : null,
            'document_date' => $this->document_date,
            'due_date' => $this->due_date !== '' ? $this->due_date : null,
            'currency_code' => $this->currency_code,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount !== '' ? $this->discount_amount : 0,
            'status' => $this->status,
            'notes' => $this->notes !== '' ? $this->notes : null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->purchaseOrderId) {
            $po = PurchaseOrder::findOrFail($this->purchaseOrderId);
            $po->update($data);
        } else {
            $po = PurchaseOrder::create($data);
        }

        $po->lines()->delete();
        foreach ($titledLines as $i => $line) {
            $po->lines()->create([
                'line_order' => $i,
                'title' => $line['title'],
                'description' => ($line['description'] ?? '') !== '' ? $line['description'] : null,
                'unit_price' => (float) ($line['unit_price'] ?? 0),
                'quantity' => (float) ($line['quantity'] ?? 1),
                'line_total' => (float) ($line['line_total'] ?? 0),
            ]);
        }

        session()->flash('toast', $this->purchaseOrderId ? 'تم تحديث فاتورة المشتريات' : 'تم إضافة فاتورة المشتريات بنجاح');
        $this->redirect(route('purchase-orders.index'), navigate: true);
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get();

        $subtotal = collect($this->lines)
            ->filter(fn ($l) => trim((string) ($l['title'] ?? '')) !== '')
            ->sum(fn ($l) => (float) ($l['line_total'] ?? 0));

        return view('livewire.purchase-order-form', [
            'suppliers' => $suppliers,
            'subtotal' => $subtotal,
        ]);
    }
}
