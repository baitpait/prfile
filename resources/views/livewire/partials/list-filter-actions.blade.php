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
            x-on:mousedown="
                const form = $el.closest('form');
                if (!form) return;
                form.querySelectorAll('input, select, textarea').forEach((field) => {
                    const wireAttr = Array.from(field.attributes).find((a) => a.name.startsWith('wire:model'));
                    if (!wireAttr) return;
                    const prop = wireAttr.value;
                    const value = field.type === 'checkbox' ? field.checked : field.value;
                    $wire.$set(prop, value);
                });
            "
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
