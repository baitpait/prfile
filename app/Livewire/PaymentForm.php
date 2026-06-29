<?php

namespace App\Livewire;

use App\Livewire\Concerns\FiltersClientsForSelect;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Services\Finance\PaymentMethod;
use Livewire\Component;

class PaymentForm extends Component
{
    use FiltersClientsForSelect;
    public ?int $recordId = null;

    public string $client_id = '';

    public string $amount = '';

    public string $currency_code = 'ILS';

    public string $paid_at = '';

    public string $payment_method = 'cash';

    public string $bank_reference = '';

    public string $notes = '';

    public string $clientSearch = '';

    public function mount(?ClientPayment $payment = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        if ($payment && $payment->exists) {
            $payment->loadMissing('client');
            $this->recordId = $payment->id;
            $this->client_id = (string) $payment->client_id;
            $this->clientSearch = $payment->client?->displayName() ?? '';
            $this->amount = (string) $payment->amount;
            $this->currency_code = $payment->currency_code ?? 'ILS';
            $this->paid_at = $payment->paid_at?->format('Y-m-d') ?? '';
            $this->payment_method = PaymentMethod::normalize($payment->method);
            $this->bank_reference = $payment->bank_reference ?? '';
            $this->notes = $payment->notes ?? '';
        } else {
            $this->paid_at = now()->format('Y-m-d');
            $this->prefillClientSelect(request()->integer('client'));
        }
    }

    public function save(): void
    {
        $this->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'paid_at' => 'required|date',
            'payment_method' => PaymentMethod::validationRule(),
        ]);

        $data = [
            'client_id' => $this->client_id,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'paid_at' => $this->paid_at,
            'method' => $this->payment_method,
            'bank_reference' => $this->bank_reference ?: null,
            'notes' => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->recordId) {
            ClientPayment::findOrFail($this->recordId)->update($data);
        } else {
            ClientPayment::create($data);
        }

        session()->flash('toast', $this->recordId ? 'تم تحديث الدفعة' : 'تم تسجيل الدفعة بنجاح');
        $this->redirect(route('payments.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.payment-form', [
            'clients' => $this->clientsForSelect(),
        ]);
    }
}
