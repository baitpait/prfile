<?php

namespace App\Policies;

use App\Models\ReceivedCheck;
use App\Models\User;
use App\Services\Finance\ReceivedCheckStatus;

class ReceivedCheckPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, ReceivedCheck $receivedCheck): bool
    {
        return (bool) $user->is_active;
    }

    public function updateStatus(User $user, ReceivedCheck $receivedCheck): bool
    {
        return $user->isAccountant();
    }

    public function endorse(User $user, ReceivedCheck $receivedCheck): bool
    {
        return $user->isAccountant() && $receivedCheck->isPending();
    }

    public function reverseClientPayment(User $user, ReceivedCheck $receivedCheck): bool
    {
        return $user->isAccountant()
            && $receivedCheck->status === ReceivedCheckStatus::NOT_CLEARED;
    }

    /**
     * Business Purpose: Export due-date register report (PDF/CSV) — accountant and manager only.
     */
    public function exportDueReport(User $user): bool
    {
        return $user->is_active && $user->isAccountant();
    }
}
