<?php

namespace App\Livewire;

use App\Models\IncomeEntry;
use Livewire\Component;

class IncomeEntryForm extends Component
{
    public ?int $recordId = null;

    public string $description = '';

    public string $amount = '';

    public string $currency_code = 'ILS';

    public string $income_date = '';

    public string $notes = '';

    public function mount(?IncomeEntry $incomeEntry = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        if ($incomeEntry && $incomeEntry->exists) {
            $this->recordId = $incomeEntry->id;
            $this->description = $incomeEntry->description ?? '';
            $this->amount = (string) $incomeEntry->amount;
            $this->currency_code = $incomeEntry->currency_code ?? 'ILS';
            $this->income_date = $incomeEntry->income_date?->format('Y-m-d') ?? '';
            $this->notes = $incomeEntry->notes ?? '';
        } else {
            $this->income_date = now()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->validate([
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'income_date' => 'required|date',
        ]);

        $data = [
            'description' => $this->description,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'income_date' => $this->income_date,
            'notes' => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->recordId) {
            IncomeEntry::findOrFail($this->recordId)->update($data);
        } else {
            IncomeEntry::create($data);
        }

        session()->flash('toast', $this->recordId ? 'تم تحديث الإيراد' : 'تم تسجيل الإيراد بنجاح');
        $this->redirect(route('income-entries.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.income-entry-form');
    }
}
