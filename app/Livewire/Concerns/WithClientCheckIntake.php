<?php

namespace App\Livewire\Concerns;

use App\Models\ClientPayment;
use App\Services\Finance\ClientPaymentReceivedCheckService;
use App\Services\Finance\PaymentMethod;

/**
 * Business Purpose: Shared form state for receiving a client check (intake into the register).
 */
trait WithClientCheckIntake
{
    public string $check_bank_name = '';

    public string $check_drawer_name = '';

    public string $check_number = '';

    public string $check_due_date = '';

    public string $check_notes = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $check_image = null;

    public ?string $existing_check_image_path = null;

    /**
     * @return array<string, string>
     */
    protected function clientCheckValidationAttributes(): array
    {
        return [
            'check_bank_name' => 'اسم البنك',
            'check_drawer_name' => 'صاحب الشيك',
            'check_number' => 'رقم الشيك',
            'check_due_date' => 'تاريخ الاستحقاق',
            'check_image' => 'صورة الشيك',
        ];
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function mergeClientCheckValidationRules(array $rules, string $methodProperty = 'payment_method'): array
    {
        $method = property_exists($this, $methodProperty) ? $this->{$methodProperty} : '';

        if (PaymentMethod::normalize($method) === PaymentMethod::CHECK) {
            $rules['check_bank_name'] = 'required|string|max:255';
            $rules['check_drawer_name'] = 'required|string|max:255';
            $rules['check_number'] = 'required|string|max:64';
            $rules['check_due_date'] = 'required|date';
            $rules['check_image'] = 'nullable|image|max:5120';
        }

        return $rules;
    }

    protected function resetClientCheckFields(): void
    {
        $this->check_bank_name = '';
        $this->check_drawer_name = '';
        $this->check_number = '';
        $this->check_due_date = '';
        $this->check_notes = '';
        $this->check_image = null;
    }

    protected function handleClientCheckPaymentMethodChanged(string $value): void
    {
        if (PaymentMethod::normalize($value) !== PaymentMethod::CHECK) {
            $this->resetClientCheckFields();
        }
    }

    protected function bankReferenceForCheckPayment(): ?string
    {
        return $this->check_number !== '' ? $this->check_number : null;
    }

    /**
     * @return array{bank_name: string, drawer_name: string, check_number: string, due_date: string, notes: ?string}
     */
    protected function clientCheckPayload(): array
    {
        return [
            'bank_name' => $this->check_bank_name,
            'drawer_name' => $this->check_drawer_name,
            'check_number' => $this->check_number,
            'due_date' => $this->check_due_date,
            'notes' => $this->check_notes ?: null,
        ];
    }

    protected function syncClientPaymentReceivedCheck(ClientPayment $payment): void
    {
        app(ClientPaymentReceivedCheckService::class)->sync(
            $payment,
            $this->clientCheckPayload(),
            $this->check_image,
            $this->existing_check_image_path,
        );
    }

    protected function deletePendingReceivedCheckForPayment(ClientPayment $payment): void
    {
        app(ClientPaymentReceivedCheckService::class)->deletePendingForPayment($payment);
    }
}
