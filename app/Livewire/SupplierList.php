<?php

namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function deleteRecord(int $id): void
    {
        Supplier::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف المورد');
    }

    public function render()
    {
        $rows = Supplier::query()
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn($q) =>
                    $q->where('business_name', 'like', $s)
                      ->orWhere('first_name',   'like', $s)
                      ->orWhere('last_name',    'like', $s)
                      ->orWhere('email',        'like', $s)
                      ->orWhere('phone_primary','like', $s)
                      ->orWhere('city',         'like', $s)
                );
            })
            ->latest()->paginate(15);

        return view('livewire.supplier-list', ['rows' => $rows]);
    }
}
