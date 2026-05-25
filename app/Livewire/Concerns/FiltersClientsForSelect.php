<?php

namespace App\Livewire\Concerns;

use App\Models\Client;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Filter client dropdown by typed name, phone, or email on Livewire forms.
 */
trait FiltersClientsForSelect
{
    /**
     * @return Collection<int, Client>
     */
    protected function clientsForSelect(): Collection
    {
        $query = Client::query()
            ->whereNull('deleted_at')
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->orderBy('id');

        $term = trim($this->clientSearch);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('business_name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('phone_primary', 'like', $like)
                    ->orWhere('phone_secondary', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        $clients = $query->limit(80)->get();

        if ($this->client_id !== '') {
            $selectedId = (int) $this->client_id;
            if ($selectedId > 0 && ! $clients->contains('id', $selectedId)) {
                $selected = Client::query()->whereKey($selectedId)->whereNull('deleted_at')->first();
                if ($selected !== null) {
                    $clients->prepend($selected);
                }
            }
        }

        return $clients;
    }

    protected function prefillClientSelect(int $clientId): void
    {
        if ($clientId <= 0) {
            return;
        }

        $client = Client::query()->whereKey($clientId)->whereNull('deleted_at')->first();
        if ($client === null) {
            return;
        }

        $this->client_id = (string) $client->id;
        $this->clientSearch = $client->displayName();
    }
}
