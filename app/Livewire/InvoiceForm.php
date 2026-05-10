<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Invoice;
use Livewire\Component;

class InvoiceForm extends Component
{
    public ?int $invoiceId = null;

    public string $client_id         = '';
    public string $legacy_invoice_no = '';
    public string $document_date     = '';
    public string $due_date          = '';
    public string $currency_code     = 'ILS';
    public string $total_amount      = '0';
    public string $discount_amount   = '0';
    public string $status            = 'draft';
    public string $notes             = '';

    /** @var array<int, array{title:string, description:string, unit_price:string, quantity:string, line_total:string}> */
    public array $lines = [];

    public function mount(?Invoice $invoice = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        if ($invoice && $invoice->exists) {
            $invoice->load('lines');
            $this->invoiceId         = $invoice->id;
            $this->client_id         = (string) $invoice->client_id;
            $this->legacy_invoice_no = $invoice->legacy_invoice_no ?? '';
            $this->document_date     = $invoice->document_date?->format('Y-m-d') ?? '';
            $this->due_date          = $invoice->due_date?->format('Y-m-d')      ?? '';
            $this->currency_code     = $invoice->currency_code   ?? 'ILS';
            $this->total_amount      = (string) $invoice->total_amount;
            $this->discount_amount   = (string) ($invoice->discount_amount ?? 0);
            $this->status            = $invoice->status ?? 'draft';
            $this->notes             = $invoice->notes  ?? '';
            $this->lines             = $invoice->lines->map(fn($l) => [
                'title'       => $l->title       ?? '',
                'description' => $l->description ?? '',
                'unit_price'  => (string) $l->unit_price,
                'quantity'    => (string) $l->quantity,
                'line_total'  => (string) $l->line_total,
            ])->toArray();
        }
    }

    public function updatedLines(mixed $value, string $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) === 2 && in_array($parts[1], ['unit_price', 'quantity'])) {
            $i = (int) $parts[0];
            $price = (float) ($this->lines[$i]['unit_price'] ?? 0);
            $qty   = (int) ($this->lines[$i]['quantity']   ?? 1);
            $this->lines[$i]['line_total'] = (string) round($price * $qty, 4);
            $this->recalcTotal();
        }
    }

    public function updatedDiscountAmount(): void { $this->recalcTotal(); }

    private function recalcTotal(): void
    {
        $subtotal = collect($this->lines)->sum(fn($l) => (float)($l['line_total'] ?? 0));
        $net = max(0, $subtotal - (float)($this->discount_amount ?? 0));
        $this->total_amount = (string) round($net, 2);
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'title'       => '',
            'description' => '',
            'unit_price'  => '',
            'quantity'    => '1',
            'line_total'  => '0',
        ];
    }

    public function removeLine(int $index): void
    {
        array_splice($this->lines, $index, 1);
        $this->lines = array_values($this->lines);
        $this->recalcTotal();
    }

    public function save(): void
    {
        $this->validate([
            'client_id'         => 'required|exists:clients,id',
            'document_date'     => 'required|date',
            'due_date'          => 'nullable|date|after_or_equal:document_date',
            'currency_code'     => 'required|string|size:3',
            'status'            => 'required|in:draft,issued,cancelled',
            'lines'             => 'array',
            'lines.*.title'     => 'required_with:lines|string|max:500',
            'lines.*.quantity'  => 'required_with:lines|integer|min:0',
            'lines.*.unit_price'=> 'required_with:lines|numeric|min:0',
        ], [
            'lines.*.title.required_with'     => 'اسم البند مطلوب',
            'lines.*.quantity.required_with'  => 'الكمية مطلوبة',
            'lines.*.unit_price.required_with'=> 'السعر مطلوب',
        ], [
            'client_id'     => 'العميل',
            'document_date' => 'تاريخ الفاتورة',
            'due_date'      => 'تاريخ الاستحقاق',
            'currency_code' => 'العملة',
            'status'        => 'الحالة',
        ]);

        if (!empty($this->lines)) {
            $subtotal = collect($this->lines)->sum(fn($l) => (float)($l['line_total'] ?? 0));
            $this->total_amount = (string) max(0, round($subtotal - (float)($this->discount_amount ?? 0), 2));
        }

        if (empty($this->total_amount) || (float)$this->total_amount < 0) {
            $this->addError('total_amount', 'المبلغ الإجمالي مطلوب');
            return;
        }

        $data = [
            'client_id'           => $this->client_id,
            'legacy_invoice_no'   => $this->legacy_invoice_no ?: null,
            'document_date'       => $this->document_date,
            'due_date'            => $this->due_date ?: null,
            'currency_code'       => $this->currency_code,
            'total_amount'        => $this->total_amount,
            'discount_amount'     => $this->discount_amount ?: 0,
            'status'              => $this->status,
            'notes'               => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->invoiceId) {
            $invoice = Invoice::findOrFail($this->invoiceId);
            $invoice->update($data);
        } else {
            $invoice = Invoice::create($data);
        }

        $invoice->lines()->delete();
        foreach ($this->lines as $i => $line) {
            if (trim($line['title'] ?? '') === '') continue;
            $invoice->lines()->create([
                'line_order'  => $i + 1,
                'title'       => $line['title'],
                'description' => $line['description'] ?: null,
                'unit_price'  => (float)($line['unit_price'] ?? 0),
                'quantity'    => (int)($line['quantity']   ?? 1),
                'line_total'  => (float)($line['line_total'] ?? 0),
            ]);
        }

        session()->flash('toast', $this->invoiceId ? 'تم تحديث الفاتورة' : 'تم إضافة الفاتورة بنجاح');
        $this->redirect(route('invoices.index'), navigate: true);
    }

    public function render()
    {
        $clients = Client::orderBy('business_name')->orderBy('first_name')->get();

        return view('livewire.invoice-form', [
            'clients'  => $clients,
            'subtotal' => collect($this->lines)->sum(fn($l) => (float)($l['line_total'] ?? 0)),
        ]);
    }
}
