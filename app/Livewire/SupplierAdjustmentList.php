<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Supplier;
use App\Models\SupplierBalanceAdjustment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierAdjustmentList extends Component
{
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    public string $supplierSearch = '';

    public string $supplier_id = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function goCreate(): void
    {
        $this->validate(['supplier_id' => 'required|exists:suppliers,id'], [], ['supplier_id' => 'المورد']);

        $this->redirect(route('suppliers.adjustments.create', (int) $this->supplier_id), navigate: true);
    }

    public function deleteRecord(int $id): void
    {
        abort_unless(auth()->user()->isManager(), 403);
        SupplierBalanceAdjustment::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف التسوية');
    }

    public function render()
    {
        $like = trim($this->supplierSearch) !== '' ? '%'.trim($this->supplierSearch).'%' : null;
        $suppliers = Supplier::query()
            ->whereNull('deleted_at')
            ->when($like, function ($q) use ($like) {
                $q->where(function ($q) use ($like): void {
                    $q->where('business_name', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like);
                });
            })
            ->orderBy('business_name')
            ->limit(80)
            ->get();

        if ($this->supplier_id !== '' && ! $suppliers->contains('id', (int) $this->supplier_id)) {
            $selected = Supplier::query()->find((int) $this->supplier_id);
            if ($selected) {
                $suppliers->prepend($selected);
            }
        }

        $rows = $this->paginateWithPerPage(
            SupplierBalanceAdjustment::query()
                ->with('supplier')
                ->whereNull('deleted_at')
                ->when($this->search !== '', function ($q) {
                    $s = '%'.$this->search.'%';
                    $q->where(function ($q) use ($s): void {
                        $q->where('reason', 'like', $s)
                            ->orWhere('notes', 'like', $s)
                            ->orWhereHas('supplier', fn ($q) => $q->where('business_name', 'like', $s)
                                ->orWhere('first_name', 'like', $s)
                                ->orWhere('last_name', 'like', $s));
                    });
                })
                ->latest('adjustment_date')
                ->latest('id')
        );

        return view('livewire.supplier-adjustment-list', [
            'rows' => $rows,
            'suppliers' => $suppliers,
        ]);
    }
}
