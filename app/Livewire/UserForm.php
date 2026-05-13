<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UserForm extends Component
{
    public ?int $userId = null;

    public string $full_name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'viewer';

    public bool $is_active = true;

    public function mount(?User $user = null): void
    {
        abort_unless(auth()->user()->isManager(), 403);
        if ($user && $user->exists) {
            $this->userId = $user->id;
            $this->full_name = $user->full_name;
            $this->email = $user->email;
            $this->password = '';
            $this->role = $user->role;
            $this->is_active = (bool) $user->is_active;
        }
    }

    public function save(): void
    {
        $rules = [
            'full_name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email'.($this->userId ? ",{$this->userId}" : ''),
            'role' => 'required|in:manager,accountant,viewer',
        ];

        if (! $this->userId || $this->password !== '') {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules, [], [
            'full_name' => 'الاسم الكامل',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'role' => 'الصلاحية',
        ]);

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            if ($user->id === auth()->id() && $this->role !== 'manager') {
                $this->addError('role', 'لا يمكنك تغيير صلاحيتك الخاصة');

                return;
            }
            $data = ['full_name' => $this->full_name, 'email' => $this->email, 'role' => $this->role, 'is_active' => $this->is_active];
            if ($this->password !== '') {
                $data['password'] = Hash::make($this->password);
            }
            $user->update($data);
            $msg = 'تم تحديث بيانات المستخدم';
        } else {
            User::create([
                'full_name' => $this->full_name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'is_active' => $this->is_active,
            ]);
            $msg = 'تم إضافة المستخدم بنجاح';
        }

        session()->flash('toast', $msg);
        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.user-form');
    }
}
