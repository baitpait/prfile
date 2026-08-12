<div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="{{ route('supplier-payments.index') }}" wire:navigate
           style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:1px solid #E2E4E9;background:#fff;color:#6B7280;text-decoration:none;"
           onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 style="font-size:18px;font-weight:800;color:#3D3D3D;line-height:1.2;">
                {{ $recordId ? 'تعديل دفعة المورد' : 'تسجيل دفعة مورد جديدة' }}
            </h1>
            @if($recordId)
            <p style="font-size:12px;color:#9CA3AF;margin-top:2px;">تعديل الدفعة رقم #{{ $recordId }}</p>
            @endif
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('supplier-payments.index') }}" wire:navigate class="btn btn-secondary" style="font-size:13px;text-decoration:none;">إلغاء</a>
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary" style="font-size:13px;min-width:120px;">
            <span wire:loading.remove wire:target="save">حفظ الدفعة</span>
            <span wire:loading wire:target="save">جاري الحفظ...</span>
        </button>
    </div>
</div>

<div class="card" style="max-width:680px;margin:0 auto;padding:28px;">
    <div style="display:flex;flex-direction:column;gap:18px;">

        <div>
            <label class="label">المورد <span class="text-red-400">*</span></label>
            <select wire:model.live="supplier_id" class="input select">
                <option value="">— اختر المورد —</option>
                @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->displayName() }}</option>@endforeach
            </select>
            @error('supplier_id')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label">ربط الدفعة <span class="text-red-400">*</span></label>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#3D3D3D;cursor:pointer;">
                    <input type="radio" wire:model.live="allocation_mode" value="account">
                    عموم الحساب
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#3D3D3D;cursor:pointer;">
                    <input type="radio" wire:model.live="allocation_mode" value="purchase_order">
                    على أمر شراء (المبلغ = إجمالي الأمر)
                </label>
            </div>
            @error('allocation_mode')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        @if($allocation_mode === 'purchase_order')
        <div>
            <label class="label">أمر الشراء <span class="text-red-400">*</span></label>
            <select wire:model.live="purchase_order_id" class="input select">
                <option value="">— اختر أمر شراء مفتوح —</option>
                @foreach($openPurchaseOrders as $po)
                    @php $poNo = $po->legacy_po_no ?? '#'.$po->id; @endphp
                    <option value="{{ $po->id }}">
                        {{ $poNo }} — {{ $po->document_date?->format('Y-m-d') }} — {{ number_format((float) $po->total_amount, 2) }} {{ $po->currency_code }}
                    </option>
                @endforeach
            </select>
            @error('purchase_order_id')<p class="field-error">{{ $message }}</p>@enderror
            @if($supplier_id !== '' && $openPurchaseOrders->isEmpty())
                <p class="text-xs text-amber-700 mt-1">لا توجد أوامر شراء صادرة متاحة بنفس العملة وغير مربوطة بدفعة.</p>
            @endif
        </div>
        @endif

        <div style="display:flex;gap:16px;">
            <div style="flex:1;">
                <label class="label">المبلغ <span class="text-red-400">*</span></label>
                <input wire:model.live="amount" type="number" step="0.01" min="0.01" dir="ltr" class="input"
                       @if($allocation_mode === 'purchase_order') readonly @endif>
                @error('amount')<p class="field-error">{{ $message }}</p>@enderror
                @if($allocation_mode === 'purchase_order')
                    <p class="text-xs text-gray-400 mt-1">يُعبَّأ تلقائياً من إجمالي أمر الشراء ولا يُوزَّع.</p>
                @endif
            </div>
            <div style="flex:1;">
                <label class="label">العملة <span class="text-red-400">*</span></label>
                <select wire:model.live="currency_code" class="input select" @if($allocation_mode === 'purchase_order' && $purchase_order_id !== '') disabled @endif>
                    <option value="ILS">ILS — شيكل</option>
                    <option value="USD">USD — دولار</option>
                    <option value="JOD">JOD — دينار</option>
                    <option value="EUR">EUR — يورو</option>
                </select>
                @if($allocation_mode === 'purchase_order' && $purchase_order_id !== '')
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

        @if($canSelectPendingCheck && $isCheckPayment)
        @include('livewire.partials.pending-check-select', [
            'pendingChecks' => $pendingChecks,
            'selectedCheck' => $selectedCheck,
            'currencyFilter' => $currencyFilter,
            'amountFilter' => $amountFilter,
        ])
        @elseif($recordId && $isCheckPayment)
        <div class="sm:col-span-2 space-y-3">
            @include('livewire.partials.pending-check-edit-notice')
            <div>
                <label class="label">مرجع بنكي</label>
                <input wire:model="bank_reference" type="text" dir="ltr" class="input">
            </div>
        </div>
        @else
        <div>
            <label class="label">رقم المرجع / الشيك</label>
            <input wire:model="bank_reference" type="text" dir="ltr" class="input">
        </div>
        @endif

        <div>
            <label class="label">ملاحظات</label>
            <textarea wire:model="notes" rows="3" class="input"></textarea>
        </div>

    </div>
</div>

</div>
