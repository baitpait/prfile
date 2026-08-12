<?php

namespace App\Livewire\Concerns;

use App\Models\ReceivedCheck;
use App\Services\Finance\PaymentMethod;
use App\Services\Finance\PendingReceivedCheckSelectService;

/**
 * Business Purpose: Shared UI state for picking a pending check from the register vs manual check entry.
 */
trait WithPendingReceivedCheckSelection
{
    /** register | manual */
    public string $check_source = 'register';

    public string $received_check_id = '';

    public function isCheckPaymentMethod(): bool
    {
        $method = property_exists($this, 'payment_method')
            ? $this->payment_method
            : ($this->method ?? '');

        return PaymentMethod::normalize($method) === PaymentMethod::CHECK;
    }

    public function canSelectPendingCheck(): bool
    {
        return ! $this->recordId && $this->isCheckPaymentMethod();
    }

    /**
     * Business Purpose: True when the form is on the «شيك من الصندوق» path (create + check method).
     */
    public function isRegisterCheckPath(): bool
    {
        return $this->canSelectPendingCheck() && $this->check_source === 'register';
    }

    public function updatedPaymentMethod(): void
    {
        if (! $this->isCheckPaymentMethod()) {
            $this->resetPendingCheckSelection();
        }
    }

    public function updatedMethod(): void
    {
        $this->updatedPaymentMethod();
    }

    public function updatedCheckSource(): void
    {
        if ($this->check_source !== 'register') {
            $this->received_check_id = '';
        }
    }

    public function updatedReceivedCheckId(): void
    {
        if ($this->received_check_id === '') {
            return;
        }

        $check = ReceivedCheck::query()
            ->whereKey((int) $this->received_check_id)
            ->where('status', \App\Services\Finance\ReceivedCheckStatus::PENDING)
            ->whereNull('deleted_at')
            ->first();

        if (! $check) {
            $this->received_check_id = '';
            $this->addError('received_check_id', 'الشيك غير متاح للتظهير.');

            return;
        }

        $this->applyPendingCheckToForm($check);
    }

    protected function resetPendingCheckSelection(): void
    {
        $this->check_source = 'register';
        $this->received_check_id = '';
    }

    /**
     * Business Purpose: Optional exact-amount filter for the pending-check dropdown (override in forms).
     */
    protected function pendingCheckFilterAmount(): ?float
    {
        return null;
    }

    protected function resolvePendingCheckFilterAmount(): ?float
    {
        $amount = $this->pendingCheckFilterAmount();
        if ($amount === null || $amount <= 0) {
            return null;
        }

        return round($amount, 2);
    }

    /**
     * Business Purpose: Drop selected check when payment amount no longer matches.
     */
    protected function reconcileRegisterCheckWithFilterAmount(): void
    {
        if (! $this->isRegisterCheckPath() || $this->received_check_id === '') {
            return;
        }

        $check = $this->selectedPendingCheck();
        $filterAmount = $this->resolvePendingCheckFilterAmount();

        if ($check === null) {
            $this->received_check_id = '';

            return;
        }

        if ($filterAmount !== null && abs(round((float) $check->amount, 2) - $filterAmount) > 0.009) {
            $this->received_check_id = '';
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, ReceivedCheck>
     */
    protected function pendingChecksForSelect(?string $currencyCode = null): \Illuminate\Support\Collection
    {
        if (! $this->canSelectPendingCheck() || $this->check_source !== 'register') {
            return collect();
        }

        return app(PendingReceivedCheckSelectService::class)->pendingFor(
            $currencyCode,
            $this->resolvePendingCheckFilterAmount(),
        );
    }

    protected function selectedPendingCheck(): ?ReceivedCheck
    {
        if ($this->received_check_id === '') {
            return null;
        }

        return ReceivedCheck::query()
            ->with('client')
            ->whereKey((int) $this->received_check_id)
            ->where('status', \App\Services\Finance\ReceivedCheckStatus::PENDING)
            ->whereNull('deleted_at')
            ->first();
    }

    abstract protected function applyPendingCheckToForm(ReceivedCheck $check): void;
}
