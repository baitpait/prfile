<?php

namespace App\Services\Reports;

use App\Models\Client;
use App\Models\Product;
use App\Services\ClientStatementService;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Simple client balance list as of a date — no aging buckets (R08).
 */
class ClientReceivablesSummaryService
{
    /**
     * @return Collection<int, array{
     *   client_id: int,
     *   client_name: string,
     *   phone: string|null,
     *   currency: string,
     *   total_invoiced: float,
     *   total_paid: float,
     *   total_adjusted: float,
     *   balance: float
     * }>
     */
    public function rows(AsOfSummaryFilters $filters): Collection
    {
        $statementService = new ClientStatementService;
        $dateTo = $filters->asOfDate->format('Y-m-d');
        $out = collect();

        $query = Client::query()->whereNull('deleted_at')->orderBy('business_name');
        if ($filters->clientId !== null) {
            $query->where('id', $filters->clientId);
        }

        $query->each(function (Client $client) use ($statementService, $dateTo, $filters, &$out): void {
            if ($filters->search !== null && ! $this->matchesSearch($client, $filters->search)) {
                return;
            }

            $statement = $statementService->forClient($client, null, $dateTo);

            foreach ($statement as $currency => $section) {
                if ($filters->currency !== null && $currency !== $filters->currency) {
                    continue;
                }

                $balance = round((float) $section['balance'], 2);
                if ($balance <= 0.00001) {
                    continue;
                }

                if ($filters->minBalance !== null && $balance < $filters->minBalance) {
                    continue;
                }

                $out->push([
                    'client_id' => $client->id,
                    'client_name' => $client->displayName(),
                    'phone' => $this->displayPhone($client),
                    'currency' => $currency,
                    'total_invoiced' => round((float) $section['total_invoiced'], 2),
                    'total_paid' => round((float) $section['total_paid'], 2),
                    'total_adjusted' => round((float) $section['total_adjusted'], 2),
                    'balance' => $balance,
                ]);
            }
        });

        return $out->sortByDesc('balance')->values();
    }

    /** @return array<string, float> */
    public function totalsByCurrency(AsOfSummaryFilters $filters): array
    {
        $totals = [];

        foreach ($this->rows($filters) as $row) {
            $cur = $row['currency'];
            $totals[$cur] = ($totals[$cur] ?? 0.0) + $row['balance'];
        }

        ksort($totals);

        return $totals;
    }

    /** @return array<string, float> */
    public function aggregateBalancesAsOf(string $dateTo, ?string $currency = null): array
    {
        $filters = new AsOfSummaryFilters(
            asOfDate: \Carbon\Carbon::parse($dateTo)->startOfDay(),
            currency: $currency,
        );

        return $this->totalsByCurrency($filters);
    }

    /** @return list<string> */
    public function currencyOptions(): array
    {
        return Product::billingCurrencies();
    }

    private function matchesSearch(Client $client, string $search): bool
    {
        $needle = mb_strtolower(trim($search));

        return str_contains(mb_strtolower($client->displayName()), $needle)
            || str_contains(mb_strtolower((string) ($client->phone_primary ?? '')), $needle)
            || str_contains(mb_strtolower((string) ($client->phone_secondary ?? '')), $needle);
    }

    private function displayPhone(Client $client): ?string
    {
        $primary = trim((string) ($client->phone_primary ?? ''));

        return $primary !== '' ? $primary : (trim((string) ($client->phone_secondary ?? '')) ?: null);
    }
}
