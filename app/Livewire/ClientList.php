<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ClientList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $confirmDeleteId = null;

    public function updatedSearch(): void { $this->resetPage(); }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void { $this->confirmDeleteId = null; }

    public function delete(): void
    {
        if ($this->confirmDeleteId) {
            Client::findOrFail($this->confirmDeleteId)->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف العميل');
        }
    }

    public function render()
    {
        $rows = Client::query()
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

        return view('livewire.client-list', ['rows' => $rows]);
    }
}
