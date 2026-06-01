<?php

namespace App\Livewire;

use App\Livewire\Concerns\FiltersCashflowList;
use App\Livewire\Concerns\FiltersSuppliersForSelect;
use App\Livewire\Concerns\UsesCommittedPaymentListFilters;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierPaymentList extends Component
{
    use FiltersCashflowList;
    use FiltersSuppliersForSelect;
    use UsesCommittedPaymentListFilters;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sp_supplier')]
    public string $filterSupplierId = '';

    public function applyListFilters(): void
    {
        $this->commitPaymentListFilterDrafts();
        $this->resetPage();
    }

    public function clearListFilters(): void
    {
        $this->search = '';
        $this->filterSupplierId = '';
        $this->supplierSearch = '';
        $this->resetCashflowFilters();
        $this->syncPaymentListFilterDrafts();
        $this->resetPage();
    }

    public function hasActiveListFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->filterSupplierId !== ''
            || trim($this->supplierSearch) !== ''
            || $this->hasActiveCashflowFilters();
    }

    public function render()
    {
        $currencies = SupplierPayment::query()
            ->whereNotNull('currency_code')
            ->where('currency_code', '!=', '')
            ->distinct()
            ->orderBy('currency_code')
            ->pluck('currency_code')
            ->values()
            ->all();

        $query = SupplierPayment::with('supplier')
            ->when($this->search, function ($q) {
                $s = '%'.$this->search.'%';
                $q->where(fn ($q) => $q->where('bank_reference', 'like', $s)
                    ->orWhere('notes', 'like', $s)
                    ->orWhereHas('supplier', fn ($q) => $q->where('business_name', 'like', $s)
                        ->orWhere('first_name', 'like', $s)
                        ->orWhere('last_name', 'like', $s)
                        ->orWhere('phone_primary', 'like', $s)
                        ->orWhere('email', 'like', $s)
                    )
                );
            })
            ->when(ctype_digit($this->filterSupplierId) && Supplier::whereKey((int) $this->filterSupplierId)->exists(), fn ($q) => $q->where('supplier_id', (int) $this->filterSupplierId)
            );

        $this->applySupplierSearchToPartyRelation($query);

        $this->applyCashflowMethodFilter($query);
        $this->applyCashflowCurrencyFilter($query, $currencies);
        $this->applyCashflowDateFilters($query, 'paid_at');

        $rows = $this->paginateWithPerPage($query->latest('paid_at'));

        return view('livewire.supplier-payment-list', [
            'rows' => $rows,
            'suppliers' => $this->suppliersForSelect(),
            'currencies' => $currencies,
        ]);
    }
}
