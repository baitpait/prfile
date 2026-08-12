<div class="bg-amber-50/60 border border-amber-200 rounded-lg p-4" style="display:flex;flex-direction:column;gap:14px;">
    <p class="text-sm font-bold text-[#3D3D3D]">مصدر الشيك</p>

    <div style="display:flex;flex-direction:column;gap:8px;">
        <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
            <input type="radio" wire:model.live="check_source" value="register">
            شيك من الصندوق (قيد المعالجة)
        </label>
        <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
            <input type="radio" wire:model.live="check_source" value="manual">
            شيك خارجي / يدوي
        </label>
    </div>

    @if($check_source === 'register')
    <div>
        <label class="label">اختر الشيك <span class="text-red-400">*</span></label>
        <select wire:model.live="received_check_id" class="input select">
            <option value="">— اختر شيكاً —</option>
            @foreach($pendingChecks as $check)
            <option value="{{ $check->id }}">{{ $check->selectLabel() }}</option>
            @endforeach
        </select>
        @error('received_check_id')<p class="field-error">{{ $message }}</p>@enderror
        @if($pendingChecks->isEmpty())
        <p class="text-xs text-amber-700 mt-1">
            لا توجد شيكات «قيد المعالجة»
            @if(($currencyFilter ?? false) && ($amountFilter ?? false))
            بهذه العملة وبنفس المبلغ
            @elseif($currencyFilter ?? false)
            بهذه العملة
            @elseif($amountFilter ?? false)
            بنفس المبلغ
            @endif
            .
        </p>
        @endif
    </div>

    @if($selectedCheck)
    <div class="text-xs text-gray-600 bg-white/70 rounded-lg p-3 space-y-1">
        <p><span class="font-semibold">العميل:</span> {{ $selectedCheck->client?->displayName() ?? '—' }}</p>
        <p><span class="font-semibold">البنك:</span> {{ $selectedCheck->bank_name }}</p>
        <p><span class="font-semibold">صاحب الشيك:</span> {{ $selectedCheck->drawer_name }}</p>
        <p dir="ltr"><span class="font-semibold">المبلغ:</span> {{ number_format((float) $selectedCheck->amount, 2) }} {{ $selectedCheck->currency_code }}</p>
    </div>
    @endif
    @else
    <div>
        <label class="label">رقم المرجع / الشيك</label>
        <input wire:model="bank_reference" type="text" dir="ltr" class="input">
    </div>
    @endif
</div>
