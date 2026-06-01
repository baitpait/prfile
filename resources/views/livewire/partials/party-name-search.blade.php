{{-- Business Purpose: Live name search for client/supplier directory lists. --}}
<div class="card p-4 mb-5">
    <label class="label">بحث</label>
    <input type="text"
           wire:model.live.debounce.300ms="search"
           class="input w-full"
           placeholder="{{ $searchPlaceholder ?? 'بحث بالاسم...' }}"
           autocomplete="off">
</div>
