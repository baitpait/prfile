<?php

namespace App\Livewire;

use App\Livewire\Concerns\UsesCommittedSearchFilter;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use AuthorizesRequests;
    use UsesCommittedSearchFilter;
    use WithPagination;
    use WithPerPagePagination;

    public string $search = '';

    public ?int $confirmDeleteId = null;

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('toggleActive', $user);
        $user->update(['is_active' => ! $user->is_active]);
        $this->dispatch('toast', message: $user->is_active ? 'تم تفعيل المستخدم' : 'تم تعطيل المستخدم');
    }

    public function confirmDelete(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(): void
    {
        if ($this->confirmDeleteId) {
            $user = User::findOrFail($this->confirmDeleteId);
            $this->authorize('delete', $user);
            $user->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف المستخدم');
        }
    }

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
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
