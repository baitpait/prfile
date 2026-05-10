<?php

namespace App\Livewire;

use App\Models\Expense;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool  $showModal       = false;
    public ?int  $editingId       = null;
    public ?int  $confirmDeleteId = null;
    public ?int  $viewingId       = null;

    public string $description   = '';
    public string $amount        = '';
    public string $currency_code = 'ILS';
    public string $expense_date  = '';
    public string $notes         = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId       = null;
        $this->confirmDeleteId = null;
        $this->viewingId       = null;
        $this->showModal       = true;
    }

    public function openEdit(int $id): void
    {
        $e = Expense::findOrFail($id);
        $this->editingId       = $id;
        $this->description     = $e->description  ?? '';
        $this->amount          = (string) $e->amount;
        $this->currency_code   = $e->currency_code ?? 'ILS';
        $this->expense_date    = $e->expense_date?->format('Y-m-d') ?? '';
        $this->notes           = $e->notes         ?? '';
        $this->confirmDeleteId = null;
        $this->viewingId       = null;
        $this->showModal       = true;
    }

    public function openView(int $id): void
    {
        $this->showModal       = false;
        $this->confirmDeleteId = null;
        $this->viewingId       = $id;
    }

    public function closeView(): void { $this->viewingId = null; }

    public function closeModal(): void { $this->showModal = false; $this->resetValidation(); }

    #[Computed]
    public function viewingRecord(): ?Expense
    {
        return $this->viewingId ? Expense::find($this->viewingId) : null;
    }

    public function save(): void
    {
        $this->validate([
            'description'   => 'required|string|max:500',
            'amount'        => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'expense_date'  => 'required|date',
        ], [], [
            'description'   => 'الوصف',
            'amount'        => 'المبلغ',
            'currency_code' => 'العملة',
            'expense_date'  => 'التاريخ',
        ]);

        $data = [
            'description'         => $this->description,
            'amount'              => $this->amount,
            'currency_code'       => $this->currency_code,
            'expense_date'        => $this->expense_date,
            'notes'               => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->editingId) {
            Expense::findOrFail($this->editingId)->update($data);
        } else {
            Expense::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $this->editingId ? 'تم تحديث المصروف' : 'تم إضافة المصروف بنجاح');
    }

    public function confirmDelete(int $id): void
    {
        $this->showModal  = false;
        $this->viewingId  = null;
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void { $this->confirmDeleteId = null; }

    public function delete(): void
    {
        if ($this->confirmDeleteId) {
            Expense::findOrFail($this->confirmDeleteId)->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف المصروف');
        }
    }

    private function resetForm(): void
    {
        $this->reset(['description','amount','expense_date','notes']);
        $this->currency_code = 'ILS';
        $this->resetValidation();
    }

    public function render()
    {
        $rows = Expense::query()
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn($q) =>
                    $q->where('description', 'like', $s)
                      ->orWhere('notes',       'like', $s)
                );
            })
            ->latest('expense_date')->paginate(15);

        return view('livewire.expense-list', [
            'rows'          => $rows,
            'viewingRecord' => $this->viewingRecord,
        ]);
    }
}
