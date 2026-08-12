<?php

namespace App\Services\Finance;

use App\Models\ReceivedCheck;
use Illuminate\Support\Collection;

/**
 * Business Purpose: List pending received checks available for endorsement in payment forms.
 */
class PendingReceivedCheckSelectService
{
    /**
     * @return Collection<int, ReceivedCheck>
     */
    public function pendingFor(?string $currencyCode = null, ?float $exactAmount = null): Collection
    {
        $query = ReceivedCheck::query()
            ->with('client')
            ->where('status', ReceivedCheckStatus::PENDING)
            ->whereNull('deleted_at')
            ->whereNull('supplier_payment_id')
            ->whereNull('salary_payment_id')
            ->whereNull('salary_advance_id');

        if ($currencyCode !== null && $currencyCode !== '') {
            $query->where('currency_code', $currencyCode);
        }

        if ($exactAmount !== null) {
            $target = round($exactAmount, 2);
            $query->where('amount', '>=', $target - 0.009)
                ->where('amount', '<=', $target + 0.009);
        }

        return $query
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }
}
