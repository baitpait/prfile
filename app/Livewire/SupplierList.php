<?php

namespace App\Livewire;

use App\Livewire\Concerns\ListsPartyDirectory;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use ListsPartyDirectory;
    use WithPagination;
    use WithPerPagePagination;

    public function deleteRecord(int $id): void
    {
        Supplier::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف المورد');
    }

    protected function partyModelClass(): string
    {
        return Supplier::class;
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage($this->partyDirectoryQuery());

        return view('livewire.supplier-list', [
            'rows' => $rows,
        ]);
    }
}
