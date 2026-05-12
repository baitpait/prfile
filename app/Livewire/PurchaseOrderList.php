<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderList extends Component
{
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'po_supplier')]
    public string $filterSupplierId = '';

    public ?int $confirmDeleteId = null;

    public ?int $viewingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSupplierId(): void
    {
        $this->resetPage();
    }

    public function openView(int $id): void
    {
        $this->confirmDeleteId = null;
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    #[Computed]
    public function viewingRecord(): ?PurchaseOrder
    {
        return $this->viewingId ? PurchaseOrder::with(['supplier', 'lines'])->find($this->viewingId) : null;
    }

    public function confirmDelete(int $id): void
    {
        Gate::authorize('delete', PurchaseOrder::findOrFail($id));
        $this->viewingId = null;
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(): void
    {
        if ($this->confirmDeleteId) {
            $po = PurchaseOrder::findOrFail($this->confirmDeleteId);
            Gate::authorize('delete', $po);
            $po->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف فاتورة المشتريات');
        }
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get();

        $query = PurchaseOrder::with('supplier')
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn ($q) => $q->where('legacy_po_no', 'like', $s)
                    ->orWhere('notes', 'like', $s)
                    ->orWhereHas('supplier', fn ($q) => $q->where('business_name', 'like', $s)
                        ->orWhere('first_name', 'like', $s)
                        ->orWhere('last_name', 'like', $s)
                    )
                );
            })
            ->when(ctype_digit($this->filterSupplierId) && Supplier::whereKey((int) $this->filterSupplierId)->exists(), fn ($q) => $q->where('supplier_id', (int) $this->filterSupplierId)
            )
            ->latest('document_date');

        $rows = $this->paginateWithPerPage($query);

        return view('livewire.purchase-order-list', [
            'rows' => $rows,
            'suppliers' => $suppliers,
            'viewingRecord' => $this->viewingRecord,
        ]);
    }
}
