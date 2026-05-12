<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierPaymentList extends Component
{
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sp_supplier')]
    public string $filterSupplierId = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSupplierId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get();

        $query = SupplierPayment::with('supplier')
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn ($q) => $q->where('bank_reference', 'like', $s)
                    ->orWhere('notes', 'like', $s)
                    ->orWhereHas('supplier', fn ($q) => $q->where('business_name', 'like', $s)
                        ->orWhere('first_name', 'like', $s)
                        ->orWhere('last_name', 'like', $s)
                    )
                );
            })
            ->when(ctype_digit($this->filterSupplierId) && Supplier::whereKey((int) $this->filterSupplierId)->exists(), fn ($q) => $q->where('supplier_id', (int) $this->filterSupplierId)
            )
            ->latest('paid_at');

        $rows = $this->paginateWithPerPage($query);

        return view('livewire.supplier-payment-list', [
            'rows' => $rows,
            'suppliers' => $suppliers,
        ]);
    }
}
