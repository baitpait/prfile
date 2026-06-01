<?php

namespace App\Livewire;

use App\Livewire\Concerns\AppliesListFiltersOnAction;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductList extends Component
{
    use AppliesListFiltersOnAction;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Product::class);
    }

    public function clearListFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function hasActiveListFilters(): bool
    {
        return trim($this->search) !== '';
    }

    public function deleteRecord(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        Gate::authorize('delete', $product);
        $product->delete();
        $this->dispatch('toast', message: 'تم حذف المنتج');
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage(
            Product::query()
                ->with('currencyPrices')
                ->when($this->search, function ($q) {
                    $s = "%{$this->search}%";
                    $q->where(fn ($q) => $q->where('name', 'like', $s)
                        ->orWhere('product_code', 'like', $s)
                        ->orWhere('description', 'like', $s)
                    );
                })
                ->latest()
        );

        return view('livewire.product-list', ['rows' => $rows]);
    }
}
