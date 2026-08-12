<div>

{{-- ── شريط العنوان العلوي ── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="{{ route('payments.index') }}" wire:navigate
           style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:1px solid #E2E4E9;background:#fff;color:#6B7280;text-decoration:none;"
           onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 style="font-size:18px;font-weight:800;color:#3D3D3D;line-height:1.2;">
                {{ $recordId ? 'تعديل الدفعة' : 'تسجيل دفعة جديدة' }}
            </h1>
            @if($recordId)
            <p style="font-size:12px;color:#9CA3AF;margin-top:2px;">تعديل الدفعة رقم #{{ $recordId }}</p>
            @endif
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('payments.index') }}" wire:navigate class="btn btn-secondary" style="font-size:13px;text-decoration:none;">إلغاء</a>
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary" style="font-size:13px;min-width:120px;">
            <svg wire:loading wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">حفظ الدفعة</span>
            <span wire:loading wire:target="save">جاري الحفظ...</span>
        </button>
    </div>
</div>

<div class="card" style="max-width:680px;margin:0 auto;padding:28px;">
    <div style="display:flex;flex-direction:column;gap:18px;">

        @include('livewire.partials.client-select-with-search', ['clients' => $clients])

        <div>
            <label class="label">ربط الدفعة <span class="text-red-400">*</span></label>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#3D3D3D;cursor:pointer;">
                    <input type="radio" wire:model.live="allocation_mode" value="account">
                    عموم الحساب
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#3D3D3D;cursor:pointer;">
                    <input type="radio" wire:model.live="allocation_mode" value="invoice">
                    على فاتورة (المبلغ = إجمالي الفاتورة)
                </label>
            </div>
            @error('allocation_mode')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        @if($allocation_mode === 'invoice')
        <div>
            <label class="label">الفاتورة <span class="text-red-400">*</span></label>
            <select wire:model.live="invoice_id" class="input select">
                <option value="">— اختر فاتورة مفتوحة —</option>
                @foreach($openInvoices as $inv)
                    @php $invNo = $inv->legacy_invoice_no ?? '#'.$inv->id; @endphp
                    <option value="{{ $inv->id }}">
                        {{ $invNo }} — {{ $inv->document_date?->format('Y-m-d') }} — {{ number_format((float) $inv->total_amount, 2) }} {{ $inv->currency_code }}
                    </option>
                @endforeach
            </select>
            @error('invoice_id')<p class="field-error">{{ $message }}</p>@enderror
            @if($client_id !== '' && $openInvoices->isEmpty())
                <p class="text-xs text-amber-700 mt-1">لا توجد فواتير صادرة متاحة بنفس العملة وغير مربوطة بدفعة.</p>
            @endif
        </div>
        @endif

        <div style="display:flex;gap:16px;">
            <div style="flex:1;">
                <label class="label">المبلغ <span class="text-red-400">*</span></label>
                <input wire:model="amount" type="number" step="0.01" min="0.01" dir="ltr" class="input"
                       @if($allocation_mode === 'invoice') readonly @endif>
                @error('amount')<p class="field-error">{{ $message }}</p>@enderror
                @if($allocation_mode === 'invoice')
                    <p class="text-xs text-gray-400 mt-1">يُعبَّأ تلقائياً من إجمالي الفاتورة ولا يُوزَّع.</p>
                @endif
            </div>
            <div style="flex:1;">
                <label class="label">العملة <span class="text-red-400">*</span></label>
                <select wire:model.live="currency_code" class="input select" @if($allocation_mode === 'invoice' && $invoice_id !== '') disabled @endif>
                    <option value="ILS">ILS — شيكل</option>
                    <option value="USD">USD — دولار</option>
                    <option value="JOD">JOD — دينار</option>
                    <option value="EUR">EUR — يورو</option>
                </select>
                @if($allocation_mode === 'invoice' && $invoice_id !== '')
                    <input type="hidden" wire:model="currency_code">
                @endif
            </div>
        </div>

        <div style="display:flex;gap:16px;">
            <div style="flex:1;">
                <label class="label">تاريخ الدفع <span class="text-red-400">*</span></label>
                <input wire:model="paid_at" type="date" class="input">
                @error('paid_at')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div style="flex:1;">
                <label class="label">طريقة الدفع <span class="text-red-400">*</span></label>
                <select wire:model.live="payment_method" class="input select">
                    <option value="cash">نقدي</option>
                    <option value="bank">بنكي</option>
                    <option value="check">شيك</option>
                    <option value="transfer">تحويل</option>
                </select>
                @error('payment_method')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        @if($payment_method === 'check')
        @include('livewire.partials.client-check-intake-fields')
        @else
        <div>
            <label class="label">رقم المرجع / الشيك</label>
            <input wire:model="bank_reference" type="text" dir="ltr" class="input">
        </div>
        @endif

        <div>
            <label class="label">ملاحظات الدفعة</label>
            <textarea wire:model="notes" rows="3" class="input"></textarea>
        </div>

    </div>
</div>

</div>
