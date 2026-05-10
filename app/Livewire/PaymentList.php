<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\ClientPayment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool  $showModal       = false;
    public ?int  $editingId       = null;
    public ?int  $confirmDeleteId = null;
    public ?int  $viewingId       = null;

    public string $client_id      = '';
    public string $amount         = '';
    public string $currency_code  = 'ILS';
    public string $paid_at        = '';
    public string $method         = 'cash';
    public string $bank_reference = '';
    public string $notes          = '';

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
        $p = ClientPayment::findOrFail($id);
        $this->editingId       = $id;
        $this->client_id       = (string) $p->client_id;
        $this->amount          = (string) $p->amount;
        $this->currency_code   = $p->currency_code  ?? 'ILS';
        $this->paid_at         = $p->paid_at?->format('Y-m-d') ?? '';
        $this->method          = $p->method         ?? 'cash';
        $this->bank_reference  = $p->bank_reference ?? '';
        $this->notes           = $p->notes          ?? '';
        $this->confirmDeleteId = null;
        $this->viewingId       = null;
        $this->showModal       = true;
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
    public function viewingRecord(): ?ClientPayment
    {
        return $this->viewingId
            ? ClientPayment::with('client')->find($this->viewingId)
            : null;
    }

    public function save(): void
    {
        $this->validate([
            'client_id'     => 'required|exists:clients,id',
            'amount'        => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'paid_at'       => 'required|date',
            'method'        => 'required|in:cash,bank,check,transfer',
        ], [], [
            'client_id'     => 'العميل',
            'amount'        => 'المبلغ',
            'currency_code' => 'العملة',
            'paid_at'       => 'تاريخ الدفع',
            'method'        => 'طريقة الدفع',
        ]);

        $data = [
            'client_id'           => $this->client_id,
            'amount'              => $this->amount,
            'currency_code'       => $this->currency_code,
            'paid_at'             => $this->paid_at,
            'method'              => $this->method,
            'bank_reference'      => $this->bank_reference ?: null,
            'notes'               => $this->notes          ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->editingId) {
            ClientPayment::findOrFail($this->editingId)->update($data);
        } else {
            ClientPayment::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $this->editingId ? 'تم تحديث الدفعة' : 'تم تسجيل الدفعة بنجاح');
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
            ClientPayment::findOrFail($this->confirmDeleteId)->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف الدفعة');
        }
    }

    private function resetForm(): void
    {
        $this->reset(['client_id','amount','paid_at','bank_reference','notes']);
        $this->currency_code = 'ILS';
        $this->method        = 'cash';
        $this->resetValidation();
    }

    public function render()
    {
        $rows = ClientPayment::with('client')
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn($q) =>
                    $q->where('bank_reference', 'like', $s)
                      ->orWhere('notes',         'like', $s)
                      ->orWhereHas('client', fn($q) =>
                          $q->where('business_name', 'like', $s)
                            ->orWhere('first_name',  'like', $s)
                            ->orWhere('last_name',   'like', $s)
                      )
                );
            })
            ->latest('paid_at')->paginate(15);

        $clients = Client::orderBy('business_name')->orderBy('first_name')->get();

        return view('livewire.payment-list', [
            'rows'          => $rows,
            'clients'       => $clients,
            'viewingRecord' => $this->viewingRecord,
        ]);
    }
}
