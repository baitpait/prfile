<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\LegacyCatalogProduct;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCatalogList extends Component
{
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $driver = LegacyCatalogProduct::query()->getConnection()->getDriverName();

        $query = LegacyCatalogProduct::query()->orderBy('id');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            if ($driver === 'sqlite') {
                $query->where(function ($q) use ($term) {
                    foreach (['$.Name', '$.flat.Name'] as $path) {
                        $q->orWhereRaw('ifnull(json_extract(payload_json, ?), \'\') like ?', [$path, $term]);
                    }
                    foreach (['$.ProductCode', '$.flat.ProductCode'] as $path) {
                        $q->orWhereRaw('ifnull(json_extract(payload_json, ?), \'\') like ?', [$path, $term]);
                    }
                    foreach (['$.Description', '$.flat.Description'] as $path) {
                        $q->orWhereRaw('ifnull(json_extract(payload_json, ?), \'\') like ?', [$path, $term]);
                    }
                });
            } else {
                $query->where(function ($q) use ($term) {
                    foreach (['$.Name', '$.flat.Name'] as $path) {
                        $q->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload_json, ?)) like ?', [$path, $term]);
                    }
                    foreach (['$.ProductCode', '$.flat.ProductCode'] as $path) {
                        $q->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload_json, ?)) like ?', [$path, $term]);
                    }
                    foreach (['$.Description', '$.flat.Description'] as $path) {
                        $q->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload_json, ?)) like ?', [$path, $term]);
                    }
                });
            }
        }

        $rows = $this->paginateWithPerPage($query);

        return view('livewire.product-catalog-list', [
            'rows' => $rows,
        ]);
    }
}
