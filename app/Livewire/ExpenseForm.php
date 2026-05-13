<?php

namespace App\Livewire;

use App\Models\Expense;
use Livewire\Component;

class ExpenseForm extends Component
{
    public ?int $recordId = null;

    public string $description = '';

    public string $amount = '';

    public string $currency_code = 'ILS';

    public string $expense_date = '';

    public string $notes = '';

    public function mount(?Expense $expense = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        if ($expense && $expense->exists) {
            $this->recordId = $expense->id;
            $this->description = $expense->description ?? '';
            $this->amount = (string) $expense->amount;
            $this->currency_code = $expense->currency_code ?? 'ILS';
            $this->expense_date = $expense->expense_date?->format('Y-m-d') ?? '';
            $this->notes = $expense->notes ?? '';
        } else {
            $this->expense_date = now()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->validate([
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'expense_date' => 'required|date',
        ]);

        $data = [
            'description' => $this->description,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'expense_date' => $this->expense_date,
            'notes' => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->recordId) {
            Expense::findOrFail($this->recordId)->update($data);
        } else {
            Expense::create($data);
        }

        session()->flash('toast', $this->recordId ? 'تم تحديث المصروف' : 'تم تسجيل المصروف بنجاح');
        $this->redirect(route('expenses.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.expense-form');
    }
}
