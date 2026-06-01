<?php

namespace App\Livewire;

use App\Livewire\Concerns\AppliesListFiltersOnAction;
use App\Livewire\Concerns\FiltersClientsForSelect;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\ClientBalanceAdjustment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ClientAdjustmentList extends Component
{
    use AppliesListFiltersOnAction;
    use FiltersClientsForSelect;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    public string $clientSearch = '';

    public string $client_id = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);
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

    public function goCreate(): void
    {
        $this->validate(['client_id' => 'required|exists:clients,id'], [], ['client_id' => 'العميل']);

        $this->redirect(route('clients.adjustments.create', (int) $this->client_id), navigate: true);
    }

    public function deleteRecord(int $id): void
    {
        abort_unless(auth()->user()->isManager(), 403);
        ClientBalanceAdjustment::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف التسوية');
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage(
            ClientBalanceAdjustment::query()
                ->with('client')
                ->whereNull('deleted_at')
                ->when($this->search !== '', function ($q) {
                    $s = '%'.$this->search.'%';
                    $q->where(function ($q) use ($s): void {
                        $q->where('reason', 'like', $s)
                            ->orWhere('notes', 'like', $s)
                            ->orWhereHas('client', fn ($c) => $c->where('business_name', 'like', $s)
                                ->orWhere('first_name', 'like', $s)
                                ->orWhere('last_name', 'like', $s));
                    });
                })
                ->latest('adjustment_date')
                ->latest('id')
        );

        return view('livewire.client-adjustment-list', [
            'rows' => $rows,
            'clients' => $this->clientsForSelect(),
        ]);
    }
}
