<?php

namespace App\Livewire;

use App\Models\ClientPayment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $confirmDeleteId = null;

    public function updatedSearch(): void { $this->resetPage(); }

    public function confirmDelete(int $id): void
    {
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

        return view('livewire.payment-list', ['rows' => $rows]);
    }
}
