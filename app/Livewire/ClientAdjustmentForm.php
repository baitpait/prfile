<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\ClientBalanceAdjustment;
use Livewire\Component;

class ClientAdjustmentForm extends Component
{
    public Client $client;

    public ?int $recordId = null;

    public string $amount = '';

    public string $currency_code = 'ILS';

    public string $adjustment_date = '';

    public string $type = ClientBalanceAdjustment::TYPE_SETTLEMENT_DISCOUNT;

    public string $reason = '';

    public string $notes = '';

    public function mount(Client $client, ?ClientBalanceAdjustment $adjustment = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        $this->client = $client;

        if ($adjustment && $adjustment->exists) {
            abort_unless($adjustment->client_id === $client->id, 404);
            $this->recordId = $adjustment->id;
            $this->amount = (string) $adjustment->amount;
            $this->currency_code = $adjustment->currency_code ?? 'ILS';
            $this->adjustment_date = $adjustment->adjustment_date?->format('Y-m-d') ?? '';
            $this->type = $adjustment->type;
            $this->reason = $adjustment->reason ?? '';
            $this->notes = $adjustment->notes ?? '';
        } else {
            $this->adjustment_date = now()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'adjustment_date' => 'required|date',
            'type' => 'required|in:'.implode(',', array_keys(ClientBalanceAdjustment::typeLabels())),
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ], [], [
            'amount' => 'المبلغ',
            'currency_code' => 'العملة',
            'adjustment_date' => 'التاريخ',
            'type' => 'نوع التسوية',
            'reason' => 'السبب',
            'notes' => 'ملاحظات',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'amount' => $this->amount,
            'currency_code' => strtoupper($this->currency_code),
            'adjustment_date' => $this->adjustment_date,
            'type' => $this->type,
            'reason' => $this->reason ?: null,
            'notes' => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->recordId) {
            ClientBalanceAdjustment::findOrFail($this->recordId)->update($data);
            $message = 'تم تحديث التسوية';
        } else {
            ClientBalanceAdjustment::create($data);
            $message = 'تم تسجيل التسوية على الذمة';
        }

        session()->flash('toast', $message);
        $this->redirect(route('clients.statement', $this->client), navigate: true);
    }

    public function render()
    {
        return view('livewire.client-adjustment-form', [
            'typeLabels' => ClientBalanceAdjustment::typeLabels(),
        ]);
    }
}
