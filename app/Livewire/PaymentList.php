<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\ClientPayment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentList extends Component
{
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteRecord(int $id): void
    {
        ClientPayment::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف الدفعة');
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage(
            ClientPayment::with('client')
                ->when($this->search, function ($q) {
                    $s = "%{$this->search}%";
                    $q->where(fn ($q) => $q->where('bank_reference', 'like', $s)
                        ->orWhere('notes', 'like', $s)
                        ->orWhereHas('client', fn ($q) => $q->where('business_name', 'like', $s)
                            ->orWhere('first_name', 'like', $s)
                            ->orWhere('last_name', 'like', $s)
                        )
                    );
                })
                ->latest('paid_at')
        );

        return view('livewire.payment-list', ['rows' => $rows]);
    }
}
