<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Invoice;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool  $showModal       = false;
    public ?int  $editingId       = null;
    public ?int  $confirmDeleteId = null;
    public ?int  $viewingId       = null;

    public string $client_id         = '';
    public string $legacy_invoice_no = '';
    public string $document_date     = '';
    public string $due_date          = '';
    public string $currency_code     = 'ILS';
    public string $total_amount      = '';
    public string $discount_amount   = '0';
    public string $status            = 'draft';
    public string $notes             = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId       = null;
        $this->confirmDeleteId = null;
        $this->viewingId       = null;
        $this->showModal       = true;
    }

    public function openEdit(int $id): void
    {
        $inv = Invoice::findOrFail($id);
        $this->editingId         = $id;
        $this->client_id         = (string) $inv->client_id;
        $this->legacy_invoice_no = $inv->legacy_invoice_no ?? '';
        $this->document_date     = $inv->document_date?->format('Y-m-d') ?? '';
        $this->due_date          = $inv->due_date?->format('Y-m-d')      ?? '';
        $this->currency_code     = $inv->currency_code   ?? 'ILS';
        $this->total_amount      = (string) $inv->total_amount;
        $this->discount_amount   = (string) ($inv->discount_amount ?? 0);
        $this->status            = $inv->status          ?? 'draft';
        $this->notes             = $inv->notes           ?? '';
        $this->confirmDeleteId   = null;
        $this->viewingId         = null;
        $this->showModal         = true;
    }

    public function openView(int $id): void
    {
        $this->showModal       = false;
        $this->confirmDeleteId = null;
        $this->viewingId       = $id;
    }

    public function closeView(): void { $this->viewingId = null; }

    public function closeModal(): void { $this->showModal = false; $this->resetValidation(); }

    #[Computed]
    public function viewingRecord(): ?Invoice
    {
        return $this->viewingId ? Invoice::with(['client', 'lines'])->find($this->viewingId) : null;
    }

    public function save(): void
    {
        $this->validate([
            'client_id'       => 'required|exists:clients,id',
            'document_date'   => 'required|date',
            'due_date'        => 'nullable|date|after_or_equal:document_date',
            'currency_code'   => 'required|string|size:3',
            'total_amount'    => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'status'          => 'required|in:draft,issued,cancelled',
        ], [], [
            'client_id'     => 'العميل',
            'document_date' => 'تاريخ الفاتورة',
            'due_date'      => 'تاريخ الاستحقاق',
            'currency_code' => 'العملة',
            'total_amount'  => 'المبلغ الإجمالي',
            'status'        => 'الحالة',
        ]);

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

        if ($this->editingId) {
            Invoice::findOrFail($this->editingId)->update($data);
        } else {
            Invoice::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $this->editingId ? 'تم تحديث الفاتورة' : 'تم إضافة الفاتورة بنجاح');
    }

    public function confirmDelete(int $id): void
    {
        $this->showModal  = false;
        $this->viewingId  = null;
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void { $this->confirmDeleteId = null; }

    public function delete(): void
    {
        if ($this->confirmDeleteId) {
            Invoice::findOrFail($this->confirmDeleteId)->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف الفاتورة');
        }
    }

    private function resetForm(): void
    {
        $this->reset([
            'client_id','legacy_invoice_no','document_date','due_date',
            'total_amount','notes',
        ]);
        $this->currency_code   = 'ILS';
        $this->status          = 'draft';
        $this->discount_amount = '0';
        $this->resetValidation();
    }

    public function render()
    {
        $rows = Invoice::with('client')
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn($q) =>
                    $q->where('legacy_invoice_no', 'like', $s)
                      ->orWhere('notes',           'like', $s)
                      ->orWhereHas('client', fn($q) =>
                          $q->where('business_name', 'like', $s)
                            ->orWhere('first_name',  'like', $s)
                            ->orWhere('last_name',   'like', $s)
                      )
                );
            })
            ->latest('document_date')->paginate(15);

        $clients = Client::orderBy('business_name')->orderBy('first_name')->get();

        return view('livewire.invoice-list', [
            'rows'          => $rows,
            'clients'       => $clients,
            'viewingRecord' => $this->viewingRecord,
        ]);
    }
}
