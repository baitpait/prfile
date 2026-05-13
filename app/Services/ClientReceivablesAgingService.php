<?php

namespace App\Services;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClientReceivablesAgingService
{
    /**
     * فواتير صادرة للعملاء الذين لديهم رصيد مستحق (إجمالي فواتير − دفعات) في نفس العملة.
     *
     * @return Collection<int, array{
     *   client_id: int,
     *   client_name: string,
     *   currency_code: string,
     *   invoice_id: int,
     *   legacy_invoice_no: string|null,
     *   document_date: string,
     *   due_date: string|null,
     *   total_amount: float,
     *   days_since_document: int,
     *   days_overdue: int|null
     * }>
     */
    public function rows(?string $currencyFilter = null): Collection
    {
        $statementService = new ClientStatementService;
        $today = Carbon::now()->startOfDay();
        $out = collect();

        Client::query()->whereNull('deleted_at')->orderBy('id')->each(function (Client $client) use ($statementService, $currencyFilter, $today, &$out): void {
            $statement = $statementService->forClient($client);

            foreach ($statement as $currency => $section) {
                if ($currencyFilter !== null && $currencyFilter !== '' && $currency !== $currencyFilter) {
                    continue;
                }
                if ((float) $section['balance'] <= 0.00001) {
                    continue;
                }

                foreach ($section['invoices'] as $inv) {
                    $daysOverdue = null;
                    if ($inv->due_date) {
                        $due = $inv->due_date->copy()->startOfDay();
                        if ($today->gt($due)) {
                            $daysOverdue = (int) $due->diffInDays($today);
                        } else {
                            $daysOverdue = 0;
                        }
                    }

                    $out->push([
                        'client_id' => $client->id,
                        'client_name' => $client->displayName(),
                        'currency_code' => $currency,
                        'invoice_id' => $inv->id,
                        'legacy_invoice_no' => $inv->legacy_invoice_no,
                        'document_date' => $inv->document_date->toDateString(),
                        'due_date' => $inv->due_date?->toDateString(),
                        'total_amount' => (float) $inv->total_amount,
                        'days_since_document' => (int) $inv->document_date->copy()->startOfDay()->diffInDays($today),
                        'days_overdue' => $daysOverdue,
                    ]);
                }
            }
        });

        return $out->sort(function (array $a, array $b): int {
            $av = $a['days_overdue'];
            $bv = $b['days_overdue'];
            $as = $av === null ? -1 : (int) $av;
            $bs = $bv === null ? -1 : (int) $bv;
            if ($as !== $bs) {
                return $bs <=> $as;
            }

            return $b['days_since_document'] <=> $a['days_since_document'];
        })->values();
    }

    /**
     * @return list<string>
     */
    public function currenciesWithReceivables(): array
    {
        $statementService = new ClientStatementService;
        $set = [];

        Client::query()->whereNull('deleted_at')->each(function (Client $client) use ($statementService, &$set): void {
            foreach ($statementService->forClient($client) as $currency => $section) {
                if ((float) $section['balance'] > 0.00001) {
                    $set[$currency] = true;
                }
            }
        });

        return collect(array_keys($set))->sort()->values()->all();
    }
}
