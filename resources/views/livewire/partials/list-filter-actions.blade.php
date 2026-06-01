{{-- Business Purpose: Shared apply/clear buttons for list filter forms. --}}
@php
    $applyMethod = $applyMethod ?? 'applyListFilters';
    $clearMethod = $clearMethod ?? null;
    $showClear = $showClear ?? false;
@endphp
<div class="flex flex-col gap-2 sm:flex-row sm:items-center shrink-0">
    <button type="submit" class="btn btn-primary w-full sm:w-auto whitespace-nowrap">
        تطبيق الفلاتر
    </button>
    @if($showClear && $clearMethod)
    <button type="button" wire:click="{{ $clearMethod }}" class="btn btn-secondary w-full sm:w-auto whitespace-nowrap">
        مسح الفلاتر
    </button>
    @endif
</div>
