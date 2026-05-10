<?php

namespace App\Livewire;

use App\Models\IncomeEntry;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class IncomeEntryList extends Component
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
    public string $income_date   = '';
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
        $e = IncomeEntry::findOrFail($id);
        $this->editingId       = $id;
        $this->description     = $e->description  ?? '';
        $this->amount          = (string) $e->amount;
        $this->currency_code   = $e->currency_code ?? 'ILS';
        $this->income_date     = $e->income_date?->format('Y-m-d') ?? '';
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
    public function viewingRecord(): ?IncomeEntry
    {
        return $this->viewingId ? IncomeEntry::find($this->viewingId) : null;
    }

    public function save(): void
    {
        $this->validate([
            'description'   => 'required|string|max:500',
            'amount'        => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'income_date'   => 'required|date',
        ], [], [
            'description'   => 'الوصف',
            'amount'        => 'المبلغ',
            'currency_code' => 'العملة',
            'income_date'   => 'التاريخ',
        ]);

        $data = [
            'description'         => $this->description,
            'amount'              => $this->amount,
            'currency_code'       => $this->currency_code,
            'income_date'         => $this->income_date,
            'notes'               => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->editingId) {
            IncomeEntry::findOrFail($this->editingId)->update($data);
        } else {
            IncomeEntry::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $this->editingId ? 'تم تحديث الإيراد' : 'تم إضافة الإيراد بنجاح');
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
            IncomeEntry::findOrFail($this->confirmDeleteId)->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف الإيراد');
        }
    }

    private function resetForm(): void
    {
        $this->reset(['description','amount','income_date','notes']);
        $this->currency_code = 'ILS';
        $this->resetValidation();
    }

    public function render()
    {
        $rows = IncomeEntry::query()
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn($q) =>
                    $q->where('description', 'like', $s)
                      ->orWhere('notes',       'like', $s)
                );
            })
            ->latest('income_date')->paginate(15);

        return view('livewire.income-entry-list', [
            'rows'          => $rows,
            'viewingRecord' => $this->viewingRecord,
        ]);
    }
}
