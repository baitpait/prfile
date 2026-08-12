<div class="bg-amber-50/60 border border-amber-200 rounded-lg p-4" style="display:flex;flex-direction:column;gap:14px;">
    <p class="text-sm font-bold text-[#3D3D3D]">تفاصيل الشيك</p>

    <div style="display:flex;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <label class="label">اسم البنك <span class="text-red-400">*</span></label>
            <input wire:model="check_bank_name" type="text" class="input">
            @error('check_bank_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div style="flex:1;min-width:200px;">
            <label class="label">صاحب الشيك <span class="text-red-400">*</span></label>
            <input wire:model="check_drawer_name" type="text" class="input" placeholder="قد يختلف عن اسم العميل">
            @error('check_drawer_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div style="display:flex;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:160px;">
            <label class="label">رقم الشيك <span class="text-red-400">*</span></label>
            <input wire:model="check_number" type="text" dir="ltr" class="input">
            @error('check_number')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div style="flex:1;min-width:160px;">
            <label class="label">تاريخ الاستحقاق <span class="text-red-400">*</span></label>
            <input wire:model="check_due_date" type="date" class="input">
            @error('check_due_date')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="label">صورة الشيك (اختياري)</label>
        <input wire:model="check_image" type="file" accept="image/*" class="input">
        @error('check_image')<p class="field-error">{{ $message }}</p>@enderror
        @if($existing_check_image_path ?? null)
            <p class="text-xs text-gray-500 mt-1">
                صورة محفوظة —
                <a href="{{ Storage::disk('public')->url($existing_check_image_path) }}" target="_blank" class="text-[#C9A227]">عرض</a>
            </p>
        @endif
    </div>

    <div>
        <label class="label">ملاحظات الشيك</label>
        <textarea wire:model="check_notes" rows="2" class="input"></textarea>
    </div>
</div>
