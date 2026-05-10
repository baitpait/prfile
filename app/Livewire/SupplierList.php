<?php

namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool  $showModal       = false;
    public ?int  $editingId       = null;
    public ?int  $confirmDeleteId = null;
    public ?int  $viewingId       = null;

    public string $business_name   = '';
    public string $first_name      = '';
    public string $last_name       = '';
    public string $email           = '';
    public string $phone_primary   = '';
    public string $phone_secondary = '';
    public string $city            = '';
    public string $country_code    = 'PS';
    public string $notes           = '';

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
        $s = Supplier::findOrFail($id);
        $this->editingId       = $id;
        $this->business_name   = $s->business_name   ?? '';
        $this->first_name      = $s->first_name      ?? '';
        $this->last_name       = $s->last_name       ?? '';
        $this->email           = $s->email           ?? '';
        $this->phone_primary   = $s->phone_primary   ?? '';
        $this->phone_secondary = $s->phone_secondary ?? '';
        $this->city            = $s->city            ?? '';
        $this->country_code    = $s->country_code    ?? 'PS';
        $this->notes           = $s->notes           ?? '';
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
    public function viewingRecord(): ?Supplier
    {
        return $this->viewingId ? Supplier::find($this->viewingId) : null;
    }

    public function save(): void
    {
        $this->validate([
            'email'         => 'nullable|email',
            'phone_primary' => 'nullable|string|max:30',
        ], [], [
            'email'         => 'البريد الإلكتروني',
            'phone_primary' => 'رقم الهاتف',
        ]);

        if (!$this->business_name && !$this->first_name) {
            $this->addError('business_name', 'يجب إدخال اسم الشركة أو الاسم الأول');
            return;
        }

        $data = [
            'business_name'   => $this->business_name   ?: null,
            'first_name'      => $this->first_name      ?: null,
            'last_name'       => $this->last_name       ?: null,
            'email'           => $this->email           ?: null,
            'phone_primary'   => $this->phone_primary   ?: null,
            'phone_secondary' => $this->phone_secondary ?: null,
            'city'            => $this->city            ?: null,
            'country_code'    => $this->country_code    ?: null,
            'notes'           => $this->notes           ?: null,
        ];

        if ($this->editingId) {
            Supplier::findOrFail($this->editingId)->update($data);
        } else {
            Supplier::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $this->editingId ? 'تم تحديث بيانات المورد' : 'تم إضافة المورد بنجاح');
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
            Supplier::findOrFail($this->confirmDeleteId)->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف المورد');
        }
    }

    private function resetForm(): void
    {
        $this->reset([
            'business_name','first_name','last_name','email',
            'phone_primary','phone_secondary','city','notes',
        ]);
        $this->country_code = 'PS';
        $this->resetValidation();
    }

    public function render()
    {
        $rows = Supplier::query()
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn($q) =>
                    $q->where('business_name', 'like', $s)
                      ->orWhere('first_name',   'like', $s)
                      ->orWhere('last_name',    'like', $s)
                      ->orWhere('email',        'like', $s)
                      ->orWhere('phone_primary','like', $s)
                      ->orWhere('city',         'like', $s)
                );
            })
            ->latest()->paginate(15);

        return view('livewire.supplier-list', [
            'rows'          => $rows,
            'viewingRecord' => $this->viewingRecord,
        ]);
    }
}
