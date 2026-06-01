{{-- Business Purpose: Single-field search with apply button (products, users, income, …). --}}
@php
    $applyMethod = $applyMethod ?? 'applyListFilters';
    $clearMethod = $clearMethod ?? 'clearListFilters';
    $hasActive = $hasActive ?? false;
@endphp
<form wire:submit.prevent="{{ $applyMethod }}" class="card p-4 mb-5">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0 flex-1">
            <label class="label">بحث</label>
            <input type="search" wire:model="search" class="input w-full" placeholder="{{ $searchPlaceholder }}" autocomplete="off">
        </div>
        @include('livewire.partials.list-filter-actions', [
            'applyMethod' => $applyMethod,
            'clearMethod' => $clearMethod,
            'showClear' => $hasActive,
        ])
    </div>
</form>
