<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['full_name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function isAccountant(): bool
    {
        return in_array($this->role, ['accountant', 'manager']);
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
}
