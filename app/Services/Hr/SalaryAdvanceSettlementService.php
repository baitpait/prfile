<?php

namespace App\Services\Hr;

use App\Models\SalaryAdvance;
use App\Models\SalaryPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business Purpose: Link salary advances to payroll deductions — unsettled advances reduce net salary.
 */
class SalaryAdvanceSettlementService
{
    public function unsettledTotal(int $employeeId, string $currencyCode): float
    {
        return round((float) SalaryAdvance::query()
            ->where('employee_id', $employeeId)
            ->where('currency_code', $currencyCode)
            ->where('status', SalaryAdvance::STATUS_PAID)
            ->whereNull('deleted_at')
            ->sum('amount'), 2);
    }

    /**
     * @return Collection<int, SalaryAdvance>
     */
    public function unsettledAdvances(int $employeeId, string $currencyCode): Collection
    {
        return SalaryAdvance::query()
            ->where('employee_id', $employeeId)
            ->where('currency_code', $currencyCode)
            ->where('status', SalaryAdvance::STATUS_PAID)
            ->whereNull('deleted_at')
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Business Purpose: When a paid salary includes deduction, mark matching advances settled (FIFO, full amounts only).
     */
    public function settleFromSalaryPayment(SalaryPayment $payment): int
    {
        if ($payment->status !== SalaryPayment::STATUS_PAID) {
            return 0;
        }

        $remaining = round((float) $payment->deduction_amount, 2);
        if ($remaining <= 0) {
            return 0;
        }

        $settledCount = 0;

        DB::transaction(function () use ($payment, &$remaining, &$settledCount): void {
            $advances = $this->unsettledAdvances($payment->employee_id, $payment->currency_code);

            foreach ($advances as $advance) {
                $amount = round((float) $advance->amount, 2);
                if ($remaining + 0.009 < $amount) {
                    break;
                }

                $advance->update([
                    'status' => SalaryAdvance::STATUS_SETTLED,
                    'settled_salary_payment_id' => $payment->id,
                ]);

                $remaining -= $amount;
                $settledCount++;
            }
        });

        return $settledCount;
    }
}
