<?php

namespace App\Livewire;

use App\Livewire\Concerns\FiltersCashflowList;
use App\Livewire\Concerns\FiltersClientsForSelect;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Client;
use App\Models\ClientPayment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentList extends Component
{
    use FiltersCashflowList;
    use FiltersClientsForSelect;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'pay_client')]
    public string $filterClientId = '';

    public string $clientSearch = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterClientId(): void
    {
        $this->resetPage();
    }

    public function clearListFilters(): void
    {
        $this->filterClientId = '';
        $this->clientSearch = '';
        $this->resetCashflowFilters();
        $this->resetPage();
    }

    public function hasActiveListFilters(): bool
    {
        return $this->filterClientId !== ''
            || trim($this->clientSearch) !== ''
            || $this->hasActiveCashflowFilters();
    }

    public function deleteRecord(int $id): void
    {
        ClientPayment::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف الدفعة');
    }

    public function render()
    {
        $currencies = ClientPayment::query()
            ->whereNotNull('currency_code')
            ->where('currency_code', '!=', '')
            ->distinct()
            ->orderBy('currency_code')
            ->pluck('currency_code')
            ->values()
            ->all();

        $query = ClientPayment::with('client')
            ->when($this->search, function ($q) {
                $s = '%'.$this->search.'%';
                $q->where(fn ($q) => $q->where('bank_reference', 'like', $s)
                    ->orWhere('notes', 'like', $s)
                    ->orWhereHas('client', fn ($q) => $q->where('business_name', 'like', $s)
                        ->orWhere('first_name', 'like', $s)
                        ->orWhere('last_name', 'like', $s)
                        ->orWhere('phone_primary', 'like', $s)
                        ->orWhere('email', 'like', $s)
                    )
                );
            })
            ->when(ctype_digit($this->filterClientId) && Client::whereKey((int) $this->filterClientId)->exists(), fn ($q) => $q->where('client_id', (int) $this->filterClientId)
            );

        $this->applyClientSearchToPartyRelation($query);

        $this->applyCashflowMethodFilter($query);
        $this->applyCashflowCurrencyFilter($query, $currencies);
        $this->applyCashflowDateFilters($query, 'paid_at');

        $rows = $this->paginateWithPerPage($query->latest('paid_at'));

        return view('livewire.payment-list', [
            'rows' => $rows,
            'clients' => $this->clientsForSelect(),
            'currencies' => $currencies,
        ]);
    }
}
