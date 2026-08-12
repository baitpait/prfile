<?php

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\ReceivedCheck;
use App\Services\Finance\ReceivedCheckStatus;
use Illuminate\Support\Carbon;

/**
 * Business Purpose: Shared factories for received-check feature tests.
 */
function makePendingCheck(
    Client $client,
    float $amount = 1200,
    string $currency = 'ILS',
    string $status = ReceivedCheckStatus::PENDING,
    ?Carbon $dueDate = null,
    ?string $checkNumber = null,
): ReceivedCheck {
    $checkNumber ??= 'CHK-100';
    $payment = ClientPayment::query()->create([
        'client_id' => $client->id,
        'amount' => $amount,
        'currency_code' => $currency,
        'paid_at' => now(),
        'method' => 'check',
        'bank_reference' => $checkNumber,
    ]);

    return ReceivedCheck::query()->create([
        'client_id' => $client->id,
        'client_payment_id' => $payment->id,
        'bank_name' => 'بنك القدس',
        'drawer_name' => 'أحمد شخصي',
        'check_number' => $checkNumber,
        'due_date' => $dueDate ?? now()->addMonth(),
        'amount' => $amount,
        'currency_code' => $currency,
        'status' => $status,
        'not_cleared_at' => $status === ReceivedCheckStatus::NOT_CLEARED ? now() : null,
    ]);
}

function makeCheckForRegister(
    Client $client,
    float $amount,
    string $currency,
    string $status = ReceivedCheckStatus::PENDING,
    ?Carbon $dueDate = null,
): ReceivedCheck {
    return makePendingCheck(
        $client,
        $amount,
        $currency,
        $status,
        $dueDate,
        (string) random_int(1000, 9999),
    );
}
