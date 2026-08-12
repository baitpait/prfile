<?php

use App\Livewire\UserForm;
use App\Livewire\UserList;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
});

test('viewer cannot access users index route', function () {
    $this->actingAs($this->viewer)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('manager can access users index route', function () {
    $this->actingAs($this->manager)
        ->get(route('users.index'))
        ->assertOk();
});

test('viewer cannot mount user list livewire', function () {
    Livewire::actingAs($this->viewer)
        ->test(UserList::class)
        ->assertForbidden();
});

test('manager can mount user list livewire', function () {
    Livewire::actingAs($this->manager)
        ->test(UserList::class)
        ->assertOk();
});

test('viewer cannot access user create route', function () {
    $this->actingAs($this->viewer)
        ->get(route('users.create'))
        ->assertForbidden();
});

test('manager cannot delete own account via user list', function () {
    Livewire::actingAs($this->manager)
        ->test(UserList::class)
        ->call('confirmDelete', $this->manager->id)
        ->assertForbidden();
});

test('manager cannot toggle own active status via user list', function () {
    Livewire::actingAs($this->manager)
        ->test(UserList::class)
        ->call('toggleActive', $this->manager->id)
        ->assertForbidden();
});

test('manager can delete another user via user list', function () {
    $target = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

    Livewire::actingAs($this->manager)
        ->test(UserList::class)
        ->call('confirmDelete', $target->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(User::find($target->id))->toBeNull();
});

test('accountant cannot edit users', function () {
    $target = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

    $this->actingAs($this->accountant)
        ->get(route('users.edit', $target))
        ->assertForbidden();
});

test('manager cannot demote own role via user form', function () {
    Livewire::actingAs($this->manager)
        ->test(UserForm::class, ['user' => $this->manager])
        ->set('role', 'accountant')
        ->call('save')
        ->assertHasErrors(['role']);
});

test('manager can create user via user form', function () {
    Livewire::actingAs($this->manager)
        ->test(UserForm::class)
        ->set('full_name', 'مستخدم جديد')
        ->set('email', 'new-user@example.test')
        ->set('password', 'secret123')
        ->set('role', 'viewer')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    expect(User::where('email', 'new-user@example.test')->exists())->toBeTrue();
});
