<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\SupplierPayment;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Derive per purchase-order payment status from supplier-level payments (FIFO).
 * Balance adjustments are excluded — payment status reflects outbound cash/bank payments only.
 */
class PurchaseOrderPaymentAllocationService
{
    public const UNPAID = 'unpaid';

    public const PARTIAL = 'partial';

    public const PAID = 'paid';

    /**
     * @return array{allocated: float, remaining: float, total: float, status: string, label: string}|null
     */
    public function forPurchaseOrder(PurchaseOrder $purchaseOrder): ?array
    {
        if ($purchaseOrder->status !== 'issued') {
            return null;
        }

        $map = $this->forPurchaseOrders(collect([$purchaseOrder]));

        return $map[$purchaseOrder->id] ?? null;
    }

    /**
     * @param  Collection<int, PurchaseOrder>  $purchaseOrders
     * @return array<int, array{allocated: float, remaining: float, total: float, status: string, label: string}>
     */
    public function forPurchaseOrders(Collection $purchaseOrders): array
    {
        $issued = $purchaseOrders->filter(fn (PurchaseOrder $po) => $po->status === 'issued');

        if ($issued->isEmpty()) {
            return [];
        }

        $result = [];

        foreach ($issued->groupBy(fn (PurchaseOrder $po) => $po->supplier_id.'|'.($po->currency_code ?? 'ILS')) as $group) {
            /** @var PurchaseOrder $sample */
            $sample = $group->first();
            $supplierId = (int) $sample->supplier_id;
            $currency = $sample->currency_code ?? 'ILS';

            $supplierOrders = PurchaseOrder::query()
                ->where('supplier_id', $supplierId)
                ->where('status', 'issued')
                ->where('currency_code', $currency)
                ->whereNull('deleted_at')
                ->orderBy('document_date')
                ->orderBy('id')
                ->get();

            $paymentPool = (float) SupplierPayment::query()
                ->where('supplier_id', $supplierId)
                ->where('currency_code', $currency)
                ->whereNull('deleted_at')
                ->sum('amount');

            $allocations = $this->allocateFifo($supplierOrders, $paymentPool);

            foreach ($group as $purchaseOrder) {
                if (! isset($allocations[$purchaseOrder->id])) {
                    continue;
                }

                $row = $allocations[$purchaseOrder->id];
                $result[$purchaseOrder->id] = array_merge($row, [
                    'label' => self::label($row['status']),
                ]);
            }
        }

        return $result;
    }

    /**
     * @param  Collection<int, PurchaseOrder>  $purchaseOrders  ordered oldest first
     * @return array<int, array{allocated: float, remaining: float, total: float, status: string}>
     */
    public function allocateFifo(Collection $purchaseOrders, float $paymentPool): array
    {
        $out = [];

        foreach ($purchaseOrders as $purchaseOrder) {
            $total = (float) $purchaseOrder->total_amount;
            $allocated = min($total, max(0.0, $paymentPool));
            $remaining = $total - $allocated;
            $paymentPool -= $allocated;

            $status = self::UNPAID;
            if ($allocated > 0.00001 && $remaining > 0.00001) {
                $status = self::PARTIAL;
            } elseif ($remaining <= 0.00001) {
                $status = self::PAID;
            }

            $out[$purchaseOrder->id] = [
                'allocated' => round($allocated, 4),
                'remaining' => round(max(0, $remaining), 4),
                'total' => round($total, 4),
                'status' => $status,
            ];
        }

        return $out;
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::PARTIAL => 'جزئية',
            self::PAID => 'مدفوعة بالكامل',
            default => 'غير مدفوعة',
        };
    }

    public static function badgeClass(string $status): string
    {
        return match ($status) {
            self::PAID => 'badge-green',
            self::PARTIAL => 'badge-yellow',
            default => 'badge-red',
        };
    }
}
