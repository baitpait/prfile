<?php

namespace App\Services\Finance;

use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\ReceivedCheck;
use App\Models\SalaryAdvance;
use App\Models\SalaryPayment;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Business Purpose: Endorse (pass) a client check to a supplier or employee by creating a matching payment record.
 */
class ReceivedCheckEndorsementService
{
    /**
     * @throws InvalidArgumentException
     */
    public function endorseToSupplier(
        ReceivedCheck $check,
        int $supplierId,
        ?int $purchaseOrderId = null,
        ?int $recordedByUserId = null,
    ): SupplierPayment {
        $this->assertCanEndorse($check);

        if ($check->supplier_payment_id !== null) {
            throw new InvalidArgumentException('This check is already linked to a supplier payment.');
        }

        $amount = round((float) $check->amount, 2);
        $currency = $check->currency_code ?? 'ILS';

        if ($purchaseOrderId !== null) {
            $po = PurchaseOrder::query()
                ->whereKey($purchaseOrderId)
                ->where('supplier_id', $supplierId)
                ->where('currency_code', $currency)
                ->where('status', 'issued')
                ->whereNull('deleted_at')
                ->first();

            if (! $po) {
                throw new InvalidArgumentException('Purchase order is not available for this supplier and currency.');
            }

            if (abs(round((float) $po->total_amount, 2) - $amount) > 0.009) {
                throw new InvalidArgumentException('Check amount must equal the purchase order total for PO allocation.');
            }

            $alreadyLinked = SupplierPayment::query()
                ->where('purchase_order_id', $po->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($alreadyLinked) {
                throw new InvalidArgumentException('This purchase order already has a linked payment.');
            }
        }

        $clientName = $check->client?->displayName() ?? 'عميل #'.$check->client_id;

        return DB::transaction(function () use ($check, $supplierId, $purchaseOrderId, $recordedByUserId, $amount, $currency, $clientName): SupplierPayment {
            $payment = SupplierPayment::query()->create([
                'supplier_id' => $supplierId,
                'purchase_order_id' => $purchaseOrderId,
                'amount' => $amount,
                'currency_code' => $currency,
                'paid_at' => now(),
                'method' => PaymentMethod::CHECK,
                'bank_reference' => $check->check_number,
                'notes' => sprintf(
                    'تظهير شيك من %s — بنك %s — صاحب الشيك: %s — استحقاق %s',
                    $clientName,
                    $check->bank_name,
                    $check->drawer_name,
                    $check->due_date?->format('Y-m-d') ?? '—'
                ),
                'recorded_by_user_id' => $recordedByUserId,
            ]);

            $check->update([
                'status' => ReceivedCheckStatus::ENDORSED,
                'endorsed_supplier_id' => $supplierId,
                'supplier_payment_id' => $payment->id,
                'endorsed_at' => now(),
            ]);

            return $payment;
        });
    }

    /**
     * Business Purpose: Pass the physical check to an employee as a salary payment or advance.
     *
     * @param  'salary'|'advance'  $mode
     *
     * @throws InvalidArgumentException
     */
    public function endorseToEmployee(
        ReceivedCheck $check,
        int $employeeId,
        string $mode,
        ?int $periodYear = null,
        ?int $periodMonth = null,
        ?int $recordedByUserId = null,
    ): SalaryPayment|SalaryAdvance {
        $this->assertCanEndorse($check);

        if ($check->salary_payment_id !== null || $check->salary_advance_id !== null) {
            throw new InvalidArgumentException('This check is already linked to an employee payment.');
        }

        if (! in_array($mode, ['salary', 'advance'], true)) {
            throw new InvalidArgumentException('Invalid employee endorsement mode.');
        }

        $employee = Employee::query()
            ->whereKey($employeeId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $employee) {
            throw new InvalidArgumentException('Employee is not available for endorsement.');
        }

        $amount = round((float) $check->amount, 2);
        $currency = $check->currency_code ?? 'ILS';
        $clientName = $check->client?->displayName() ?? 'عميل #'.$check->client_id;
        $notes = sprintf(
            'تظهير شيك من %s — بنك %s — صاحب الشيك: %s — استحقاق %s',
            $clientName,
            $check->bank_name,
            $check->drawer_name,
            $check->due_date?->format('Y-m-d') ?? '—'
        );

        return DB::transaction(function () use ($check, $employee, $mode, $periodYear, $periodMonth, $recordedByUserId, $amount, $currency, $notes): SalaryPayment|SalaryAdvance {
            if ($mode === 'advance') {
                $advance = SalaryAdvance::query()->create([
                    'employee_id' => $employee->id,
                    'amount' => $amount,
                    'currency_code' => $currency,
                    'paid_at' => now(),
                    'method' => PaymentMethod::CHECK,
                    'bank_reference' => $check->check_number,
                    'status' => SalaryAdvance::STATUS_PAID,
                    'notes' => $notes,
                    'recorded_by_user_id' => $recordedByUserId,
                ]);

                $check->update([
                    'status' => ReceivedCheckStatus::ENDORSED,
                    'endorsed_employee_id' => $employee->id,
                    'salary_advance_id' => $advance->id,
                    'endorsed_at' => now(),
                ]);

                return $advance;
            }

            if ($periodYear === null || $periodMonth === null) {
                throw new InvalidArgumentException('Salary period is required for salary endorsement.');
            }

            $existing = SalaryPayment::query()
                ->where('employee_id', $employee->id)
                ->where('period_year', $periodYear)
                ->where('period_month', $periodMonth)
                ->where('currency_code', $currency)
                ->whereNull('deleted_at')
                ->first();

            if ($existing !== null) {
                if ($existing->status === SalaryPayment::STATUS_PAID) {
                    throw new InvalidArgumentException('This employee already has a paid salary for the selected period.');
                }

                if ($existing->status === SalaryPayment::STATUS_CANCELLED) {
                    throw new InvalidArgumentException('Salary for this period is cancelled — choose another period.');
                }

                if (abs(round((float) $existing->net_amount, 2) - $amount) > 0.009) {
                    throw new InvalidArgumentException('Check amount must equal the draft salary net amount for this period.');
                }

                $existing->update([
                    'paid_at' => now(),
                    'method' => PaymentMethod::CHECK,
                    'bank_reference' => $check->check_number,
                    'status' => SalaryPayment::STATUS_PAID,
                    'notes' => trim(($existing->notes ? $existing->notes."\n" : '').$notes),
                    'recorded_by_user_id' => $recordedByUserId ?? $existing->recorded_by_user_id,
                ]);

                $salaryPayment = $existing->fresh();
            } else {
                $salaryPayment = SalaryPayment::query()->create([
                    'employee_id' => $employee->id,
                    'period_year' => $periodYear,
                    'period_month' => $periodMonth,
                    'base_amount' => $amount,
                    'bonus_amount' => 0,
                    'deduction_amount' => 0,
                    'net_amount' => $amount,
                    'currency_code' => $currency,
                    'paid_at' => now(),
                    'method' => PaymentMethod::CHECK,
                    'bank_reference' => $check->check_number,
                    'status' => SalaryPayment::STATUS_PAID,
                    'notes' => $notes,
                    'recorded_by_user_id' => $recordedByUserId,
                ]);
            }

            $check->update([
                'status' => ReceivedCheckStatus::ENDORSED,
                'endorsed_employee_id' => $employee->id,
                'salary_payment_id' => $salaryPayment->id,
                'endorsed_at' => now(),
            ]);

            return $salaryPayment;
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function assertCanEndorse(ReceivedCheck $check): void
    {
        if (! $check->isPending()) {
            throw new InvalidArgumentException('Only pending checks can be endorsed.');
        }

        if ($check->supplier_payment_id !== null
            || $check->salary_payment_id !== null
            || $check->salary_advance_id !== null) {
            throw new InvalidArgumentException('This check is already endorsed.');
        }
    }
}
