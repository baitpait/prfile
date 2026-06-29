<?php

namespace App\Services\Reports;

use App\Models\Client;
use App\Models\Product;
use App\Services\ClientStatementService;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Period statement summary for all clients (R17) — one row per client/currency.
 */
class AggregatedClientStatementsService
{
    /**
     * @return Collection<int, array{
     *   client_id: int,
     *   client_name: string,
     *   currency: string,
     *   total_invoiced: float,
     *   total_paid: float,
     *   total_adjusted: float,
     *   balance: float,
     *   movement_count: int
     * }>
     */
    public function rows(ReportPeriodFilters $filters): Collection
    {
        $statementService = new ClientStatementService;
        $dateFrom = $filters->resolvedDateFrom()->format('Y-m-d');
        $dateTo = $filters->resolvedDateTo()->format('Y-m-d');
        $out = collect();

        $query = Client::query()->whereNull('deleted_at')->orderBy('business_name');
        if ($filters->clientId !== null) {
            $query->where('id', $filters->clientId);
        }

        $query->each(function (Client $client) use ($statementService, $dateFrom, $dateTo, $filters, &$out): void {
            $statement = $statementService->forClient($client, $dateFrom, $dateTo);

            foreach ($statement as $currency => $section) {
                if ($filters->currency !== null && $currency !== $filters->currency) {
                    continue;
                }

                $movementCount = count($section['timeline'] ?? []);
                $balance = round((float) $section['balance'], 2);

                if ($movementCount === 0 && abs($balance) < 0.00001) {
                    continue;
                }

                $out->push([
                    'client_id' => $client->id,
                    'client_name' => $client->displayName(),
                    'currency' => $currency,
                    'total_invoiced' => round((float) $section['total_invoiced'], 2),
                    'total_paid' => round((float) $section['total_paid'], 2),
                    'total_adjusted' => round((float) $section['total_adjusted'], 2),
                    'balance' => $balance,
                    'movement_count' => $movementCount,
                ]);
            }
        });

        return $out->sortBy('client_name')->sortByDesc('balance')->values();
    }

    /** @return array<string, array{invoiced: float, paid: float, adjusted: float, balance: float, clients: int}> */
    public function totalsByCurrency(ReportPeriodFilters $filters): array
    {
        $totals = [];

        foreach ($this->rows($filters) as $row) {
            $cur = $row['currency'];
            if (! isset($totals[$cur])) {
                $totals[$cur] = ['invoiced' => 0.0, 'paid' => 0.0, 'adjusted' => 0.0, 'balance' => 0.0, 'clients' => 0];
            }
            $totals[$cur]['invoiced'] += $row['total_invoiced'];
            $totals[$cur]['paid'] += $row['total_paid'];
            $totals[$cur]['adjusted'] += $row['total_adjusted'];
            $totals[$cur]['balance'] += $row['balance'];
            $totals[$cur]['clients']++;
        }

        ksort($totals);

        return $totals;
    }

    /** @return list<string> */
    public function currencyOptions(): array
    {
        return Product::billingCurrencies();
    }
}
