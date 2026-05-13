<?php

namespace App\Livewire\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

trait WithPerPagePagination
{
    #[Url(as: 'per_page', history: true)]
    public int $perPage = 50;

    public function updatedPerPage(mixed $value): void
    {
        $v = (int) $value;
        if (! in_array($v, [50, 100, 200, 0], true)) {
            $this->perPage = 50;
        } else {
            $this->perPage = $v;
        }
        $this->resetPage();
    }

    protected function paginateWithPerPage(Builder $query): LengthAwarePaginator
    {
        $pp = (int) $this->perPage;
        if (! in_array($pp, [50, 100, 200, 0], true)) {
            $pp = 50;
            $this->perPage = 50;
        }

        if ($pp === 0) {
            $total = (clone $query)->toBase()->getCountForPagination();

            return $query->paginate(max(1, $total));
        }

        return $query->paginate($pp);
    }
}
