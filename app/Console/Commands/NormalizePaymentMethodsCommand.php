<?php

namespace App\Console\Commands;

use App\Models\ClientPayment;
use App\Models\SupplierPayment;
use App\Services\Finance\PaymentMethod;
use Illuminate\Console\Command;

class NormalizePaymentMethodsCommand extends Command
{
    protected $signature = 'payments:normalize-methods {--dry-run : Preview changes without writing}';

    protected $description = 'Normalize legacy payment method values to canonical codes (cash, bank, check, transfer)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        foreach ([SupplierPayment::query(), ClientPayment::query()] as $query) {
            foreach ($query->whereNotNull('method')->cursor() as $payment) {
                $current = (string) $payment->method;
                if (PaymentMethod::isCanonical($current)) {
                    continue;
                }

                $normalized = PaymentMethod::normalize($current);
                $this->line("{$payment->getTable()} #{$payment->id}: {$current} → {$normalized}");

                if (! $dryRun) {
                    $payment->update(['method' => $normalized]);
                }

                $updated++;
            }
        }

        if ($updated === 0) {
            $this->info('لا توجد طرق دفع تحتاج تطبيعاً.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? 'سيتم تحديث' : 'تم تحديث')." {$updated} سجل.");

        return self::SUCCESS;
    }
}
