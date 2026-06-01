{{-- Business Purpose: Submit filter forms only after draft fields sync to Livewire. --}}
@props(['applyMethod' => 'applyListFilters'])

<form {{ $attributes }}
      x-on:submit.prevent="
        (async () => {
            const form = $el;
            const sets = [];
            form.querySelectorAll('input, select, textarea').forEach((field) => {
                const wireAttr = Array.from(field.attributes).find((a) => a.name.startsWith('wire:model'));
                if (!wireAttr) return;
                const prop = wireAttr.value;
                const value = field.type === 'checkbox' ? field.checked : field.value;
                sets.push($wire.$set(prop, value));
            });
            await Promise.all(sets);
            await $wire.call(@js($applyMethod));
        })()
      ">
    {{ $slot }}
</form>
