<?php

namespace App\Services\Finance;

use App\Models\ClientPayment;
use App\Models\Product;
use App\Models\ReceivedCheck;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business Purpose: Operational check register — summaries by currency and movement timeline per check.
 */
class ReceivedCheckRegisterService
{
    /**
     * @return list<string>
     */
    public function supportedCurrencies(): array
    {
        return Product::billingCurrencies();
    }

    /**
     * Pending checks grouped by currency for the register dashboard.
     *
     * @return array<string, array{count: int, total: float}>
     */
    public function pendingSummaryByCurrency(): array
    {
        $rows = ReceivedCheck::query()
            ->where('status', ReceivedCheckStatus::PENDING)
            ->whereNull('deleted_at')
            ->selectRaw('currency_code, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('currency_code')
            ->orderBy('currency_code')
            ->get();

        $summary = [];
        foreach ($rows as $row) {
            $summary[$row->currency_code] = [
                'count' => (int) $row->cnt,
                'total' => round((float) $row->total, 2),
            ];
        }

        return $summary;
    }

    /**
     * @return array{overdue: int, due_soon: int}
     */
    public function pendingDueCounts(): array
    {
        $today = now()->startOfDay();
        $soon = now()->addDays(7)->endOfDay();

        $base = ReceivedCheck::query()
            ->where('status', ReceivedCheckStatus::PENDING)
            ->whereNull('deleted_at');

        return [
            'overdue' => (clone $base)->whereDate('due_date', '<', $today)->count(),
            'due_soon' => (clone $base)
                ->whereDate('due_date', '>=', $today)
                ->whereDate('due_date', '<=', $soon)
                ->count(),
        ];
    }

    /**
     * @return Collection<int, array{date: Carbon, sort: string, label: string, detail: string, tone: string}>
     */
    public function timelineFor(ReceivedCheck $check): Collection
    {
        $check->loadMissing([
            'client',
            'clientPayment' => fn ($q) => $q->withTrashed(),
            'endorsedSupplier',
            'endorsedEmployee',
            'supplierPayment',
            'salaryPayment',
            'salaryAdvance',
        ]);

        $events = collect();

        $receivedAt = $check->clientPayment?->paid_at ?? $check->created_at;
        if ($receivedAt) {
            $events->push([
                'date' => $receivedAt instanceof Carbon ? $receivedAt : Carbon::parse($receivedAt),
                'sort' => ($receivedAt instanceof Carbon ? $receivedAt : Carbon::parse($receivedAt))->format('Y-m-d H:i:s').'_0',
                'label' => 'استلام الشيك',
                'detail' => sprintf(
                    'من %s — %s %s — %s',
                    $check->client?->displayName() ?? 'عميل',
                    number_format((float) $check->amount, 2),
                    $check->currency_code,
                    ReceivedCheckStatus::label(ReceivedCheckStatus::PENDING)
                ),
                'tone' => 'pending',
            ]);
        }

        if ($check->cleared_at) {
            $events->push([
                'date' => $check->cleared_at,
                'sort' => $check->cleared_at->format('Y-m-d H:i:s').'_1',
                'label' => 'صُرف في البنك',
                'detail' => 'تم تسجيل الشيك كـ «صُرف»',
                'tone' => 'cleared',
            ]);
        }

        if ($check->not_cleared_at) {
            $events->push([
                'date' => $check->not_cleared_at,
                'sort' => $check->not_cleared_at->format('Y-m-d H:i:s').'_2',
                'label' => 'لم يُصرف (رجوع)',
                'detail' => 'الشيك لم يُصرف — يُرجى عكس دفعة العميل إن لزم',
                'tone' => 'not_cleared',
            ]);
        }

        if ($check->endorsed_at) {
            $target = $check->endorsedSupplier?->displayName()
                ?? $check->endorsedEmployee?->displayName()
                ?? '—';
            $kind = $check->endorsed_supplier_id ? 'لمورد' : 'لموظف';
            $events->push([
                'date' => $check->endorsed_at,
                'sort' => $check->endorsed_at->format('Y-m-d H:i:s').'_3',
                'label' => 'تظهير '.$kind,
                'detail' => $target,
                'tone' => 'endorsed',
            ]);
        }

        $payment = $check->clientPayment;
        if ($payment && $payment->trashed()) {
            $deletedAt = $payment->deleted_at ?? now();
            $events->push([
                'date' => $deletedAt,
                'sort' => $deletedAt->format('Y-m-d H:i:s').'_4',
                'label' => 'عكس دفعة العميل',
                'detail' => 'تم إلغاء/عكس دفعة #'.$payment->id.' — '.$check->currency_code,
                'tone' => 'reversed',
            ]);
        }

        return $events->sortBy('sort')->values();
    }

    /**
     * Business Purpose: Reverse client payment when a check bounced — restores client receivable.
     *
     * @throws \InvalidArgumentException
     */
    public function reverseClientPayment(ReceivedCheck $check, ?int $userId = null): void
    {
        if ($check->status !== ReceivedCheckStatus::NOT_CLEARED) {
            throw new \InvalidArgumentException('Payment can only be reversed for bounced (not cleared) checks.');
        }

        $payment = ClientPayment::withTrashed()->find($check->client_payment_id);
        if (! $payment || $payment->trashed()) {
            throw new \InvalidArgumentException('Client payment is already reversed or missing.');
        }

        DB::transaction(function () use ($check, $payment, $userId): void {
            $payment->delete();

            $stamp = now()->format('Y-m-d H:i');
            $noteLine = sprintf('[%s] عُكست دفعة العميل #%d', $stamp, $payment->id);
            $notes = trim(($check->notes ? $check->notes."\n" : '').$noteLine);

            $check->update([
                'notes' => $notes,
                'recorded_by_user_id' => $userId ?? $check->recorded_by_user_id,
            ]);
        });
    }

    public function clientPaymentIsReversed(ReceivedCheck $check): bool
    {
        if (! $check->client_payment_id) {
            return false;
        }

        $payment = ClientPayment::withTrashed()->find($check->client_payment_id);

        return $payment !== null && $payment->trashed();
    }
}
