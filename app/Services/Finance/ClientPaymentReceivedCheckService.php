<?php

namespace App\Services\Finance;

use App\Models\ClientPayment;
use App\Models\ReceivedCheck;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Business Purpose: Create or update a received_checks row for a client check payment.
 */
class ClientPaymentReceivedCheckService
{
    /**
     * @param  array{bank_name: string, drawer_name: string, check_number: string, due_date: string, notes?: ?string}  $checkData
     */
    public function sync(
        ClientPayment $payment,
        array $checkData,
        TemporaryUploadedFile|null $checkImage = null,
        ?string $existingImagePath = null,
    ): void {
        $imagePath = $this->storeCheckImage($payment, $checkImage, $existingImagePath);

        $payload = [
            'client_id' => $payment->client_id,
            'bank_name' => $checkData['bank_name'],
            'drawer_name' => $checkData['drawer_name'],
            'check_number' => $checkData['check_number'],
            'due_date' => $checkData['due_date'],
            'amount' => $payment->amount,
            'currency_code' => $payment->currency_code,
            'notes' => $checkData['notes'] ?? null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($imagePath !== null) {
            $payload['image_path'] = $imagePath;
        }

        $existing = $payment->receivedCheck;
        if ($existing) {
            if ($existing->status !== ReceivedCheckStatus::PENDING) {
                return;
            }
            if ($imagePath !== null && $existing->image_path && $existing->image_path !== $imagePath) {
                $this->deleteCheckImage($existing->image_path);
            }
            $existing->update($payload);

            return;
        }

        ReceivedCheck::create(array_merge($payload, [
            'client_payment_id' => $payment->id,
            'status' => ReceivedCheckStatus::PENDING,
            'image_path' => $imagePath,
        ]));
    }

    /**
     * Business Purpose: Remove pending register entry when payment method is no longer check.
     */
    public function deletePendingForPayment(ClientPayment $payment): void
    {
        $check = $payment->receivedCheck;
        if (! $check || ! $check->isPending()) {
            return;
        }

        $this->deleteCheckImage($check->image_path);
        $check->delete();
    }

    protected function storeCheckImage(
        ClientPayment $payment,
        TemporaryUploadedFile|null $checkImage,
        ?string $existingImagePath,
    ): ?string {
        if ($checkImage) {
            $path = $checkImage->store('checks/'.$payment->client_id, 'public');

            return $path ?: null;
        }

        return $existingImagePath;
    }

    protected function deleteCheckImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
