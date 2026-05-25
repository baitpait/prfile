{{-- Requires: $clients (collection), wire models clientSearch + client_id --}}
<div>
    <label class="label">{{ $label ?? 'العميل' }} @if($required ?? true)<span class="text-red-400">*</span>@endif</label>
    <input type="search"
           wire:model.live.debounce.300ms="clientSearch"
           class="input mb-2 {{ $inputClass ?? '' }}"
           style="{{ $inputStyle ?? '' }}"
           placeholder="ابحث بالاسم أو الهاتف أو البريد..."
           autocomplete="off">
    <select wire:model="client_id" class="input select {{ $selectClass ?? '' }}" style="{{ $selectStyle ?? '' }}">
        <option value="">{{ $placeholder ?? '— اختر العميل —' }}</option>
        @forelse($clients as $c)
            <option value="{{ $c->id }}">
                {{ $c->displayName() }}
                @if($c->phone_primary) — {{ $c->phone_primary }} @endif
            </option>
        @empty
            <option value="" disabled>لا يوجد عميل مطابق — غيّر نص البحث</option>
        @endforelse
    </select>
    @if(($clientSearch ?? '') !== '' && $clients->isNotEmpty())
        <p class="text-xs text-gray-400 mt-1" style="{{ $hintStyle ?? '' }}">{{ $clients->count() }} نتيجة</p>
    @endif
    @error('client_id')<p class="field-error">{{ $message }}</p>@enderror
</div>
