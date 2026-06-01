{{-- Business Purpose: Shared apply/clear buttons for list filter forms. --}}
@php
    $applyMethod = $applyMethod ?? 'applyListFilters';
    $clearMethod = $clearMethod ?? null;
    $showClear = $showClear ?? false;
@endphp
<div class="flex flex-col gap-2 sm:flex-row sm:items-center shrink-0">
    <button type="submit"
            wire:loading.attr="disabled"
            wire:target="{{ $applyMethod }}"
            class="btn btn-primary w-full sm:w-auto whitespace-nowrap">
        <span wire:loading.remove wire:target="{{ $applyMethod }}">تطبيق الفلاتر</span>
        <span wire:loading wire:target="{{ $applyMethod }}">جاري التطبيق...</span>
    </button>
    @if($showClear && $clearMethod)
    <button type="button"
            wire:click="{{ $clearMethod }}"
            wire:loading.attr="disabled"
            wire:target="{{ $clearMethod }}"
            class="btn btn-secondary w-full sm:w-auto whitespace-nowrap">
        مسح الفلاتر
    </button>
    @endif
</div>
