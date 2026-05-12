<?php

namespace App\Livewire;

use App\Models\Supplier;
use App\Services\SupplierStatementService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierStatement extends Component
{
    public Supplier $supplier;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public array $statement = [];

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
        $this->loadStatement();
    }

    public function loadStatement(): void
    {
        $service = new SupplierStatementService;
        $this->statement = $service->forSupplier(
            $this->supplier,
            $this->dateFrom ?: null,
            $this->dateTo ?: null
        );
    }

    public function updatedDateFrom(): void
    {
        $this->loadStatement();
    }

    public function updatedDateTo(): void
    {
        $this->loadStatement();
    }

    public function resetDates(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->loadStatement();
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('exportStatement', $this->supplier);

        $service = new SupplierStatementService;
        $rows = $service->toCsvRows($this->statement);

        $name = $this->supplier->displayName();
        $filename = 'كشف-حساب-مورد-'.$name.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.supplier-statement');
    }
}
