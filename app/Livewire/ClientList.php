<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Client;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ClientList extends Component
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
        Client::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف العميل');
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage(
            Client::query()
                ->when($this->search, function ($q) {
                    $s = "%{$this->search}%";
                    $q->where(fn ($q) => $q->where('business_name', 'like', $s)
                        ->orWhere('first_name', 'like', $s)
                        ->orWhere('last_name', 'like', $s)
                        ->orWhere('email', 'like', $s)
                        ->orWhere('phone_primary', 'like', $s)
                        ->orWhere('city', 'like', $s)
                    );
                })
                ->latest()
        );

        return view('livewire.client-list', ['rows' => $rows]);
    }
}
