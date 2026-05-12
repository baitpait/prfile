<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;
    use WithPerPagePagination;

    public string $search = '';

    public ?int $confirmDeleteId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            $this->dispatch('toast', message: 'لا يمكنك تعطيل حسابك الخاص', type: 'error');

            return;
        }
        $user->update(['is_active' => ! $user->is_active]);
        $this->dispatch('toast', message: $user->is_active ? 'تم تفعيل المستخدم' : 'تم تعطيل المستخدم');
    }

    public function confirmDelete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('toast', message: 'لا يمكنك حذف حسابك الخاص', type: 'error');

            return;
        }
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(): void
    {
        if ($this->confirmDeleteId && $this->confirmDeleteId !== auth()->id()) {
            User::findOrFail($this->confirmDeleteId)->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف المستخدم');
        }
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->isManager(), 403);
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage(
            User::query()
                ->when($this->search, fn ($q) => $q->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                )
                ->orderByRaw("CASE role WHEN 'manager' THEN 0 WHEN 'accountant' THEN 1 ELSE 2 END")
                ->orderBy('full_name')
        );

        return view('livewire.user-list', ['rows' => $rows]);
    }
}
