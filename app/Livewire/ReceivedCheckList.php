<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\ReceivedCheck;
use App\Services\Finance\ReceivedCheckRegisterService;
use App\Services\Finance\ReceivedCheckStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ReceivedCheckList extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithPerPagePagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $currency = '';

    /** overdue | due_soon | '' */
    #[Url]
    public string $due = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ReceivedCheck::class);
    }

    /**
     * Business Purpose: Mark check as cleared in bank (does not reverse client payment).
     */
    public function markCleared(int $id): void
    {
        $check = ReceivedCheck::query()->findOrFail($id);
        $this->authorize('updateStatus', $check);

        if (! $check->isPending()) {
            $this->dispatch('toast', message: 'لا يمكن تغيير حالة هذا الشيك.', type: 'error');

            return;
        }

        $check->update([
            'status' => ReceivedCheckStatus::CLEARED,
            'cleared_at' => now(),
            'not_cleared_at' => null,
        ]);

        $this->dispatch('toast', message: 'تم تسجيل الشيك كـ «صُرف».');
    }

    /**
     * Business Purpose: Mark bounced/not cleared — accountant may reverse payment separately.
     */
    public function markNotCleared(int $id): void
    {
        $check = ReceivedCheck::query()->findOrFail($id);
        $this->authorize('updateStatus', $check);

        if (! $check->isPending()) {
            $this->dispatch('toast', message: 'لا يمكن تغيير حالة هذا الشيك.', type: 'error');

            return;
        }

        $check->update([
            'status' => ReceivedCheckStatus::NOT_CLEARED,
            'not_cleared_at' => now(),
            'cleared_at' => null,
        ]);

        session()->flash(
            'toast',
            'تم تسجيل «لم يُصرف». يمكنك عكس دفعة العميل من تفاصيل الشيك.'
        );
        $this->dispatch('toast', message: 'تم تسجيل «لم يُصرف» — راجع عكس الدفعة.');
    }

    public function render()
    {
        $register = app(ReceivedCheckRegisterService::class);
        $today = now()->startOfDay();
        $soon = now()->addDays(7)->endOfDay();

        $rows = $this->paginateWithPerPage(
            ReceivedCheck::query()
                ->with(['client', 'clientPayment', 'endorsedSupplier', 'endorsedEmployee'])
                ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
                ->when($this->currency !== '', fn ($q) => $q->where('currency_code', $this->currency))
                ->when($this->due === 'overdue', function ($q) use ($today) {
                    $q->where('status', ReceivedCheckStatus::PENDING)
                        ->whereDate('due_date', '<', $today);
                })
                ->when($this->due === 'due_soon', function ($q) use ($today, $soon) {
                    $q->where('status', ReceivedCheckStatus::PENDING)
                        ->whereDate('due_date', '>=', $today)
                        ->whereDate('due_date', '<=', $soon);
                })
                ->orderByDesc('due_date')
                ->orderByDesc('id')
        );

        return view('livewire.received-check-list', [
            'rows' => $rows,
            'statusOptions' => ReceivedCheckStatus::filterCodes(),
            'currencyOptions' => $register->supportedCurrencies(),
            'pendingSummary' => $register->pendingSummaryByCurrency(),
            'dueCounts' => $register->pendingDueCounts(),
        ]);
    }
}
