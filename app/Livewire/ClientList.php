<?php

namespace App\Livewire;

use App\Livewire\Concerns\FiltersPartyDirectory;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;

class ClientList extends Component
{
    use FiltersPartyDirectory;
    use WithPagination;
    use WithPerPagePagination;

    public function deleteRecord(int $id): void
    {
        Client::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف العميل');
    }

    protected function partyModelClass(): string
    {
        return Client::class;
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage($this->partyDirectoryQuery());

        return view('livewire.client-list', [
            'rows' => $rows,
            'cities' => $this->partyFilterCities(),
        ]);
    }
}
