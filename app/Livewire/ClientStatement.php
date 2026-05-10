<?php

namespace App\Livewire;

use App\Models\Client;
use App\Services\ClientStatementService;
use Livewire\Component;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientStatement extends Component
{
    public Client $client;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public array $statement = [];

    public function mount(Client $client): void
    {
        $this->client = $client;
        $this->loadStatement();
    }

    public function loadStatement(): void
    {
        $service = new ClientStatementService();
        $this->statement = $service->forClient(
            $this->client,
            $this->dateFrom ?: null,
            $this->dateTo   ?: null
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

    public function exportCsv(): StreamedResponse
    {
        $service = new ClientStatementService();
        $rows = $service->toCsvRows($this->statement);

        $clientName = $this->client->displayName();
        $filename = "كشف-حساب-{$clientName}-" . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.client-statement');
    }
}
