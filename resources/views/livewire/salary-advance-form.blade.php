<div>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">{{ $recordId ? 'تعديل سلفة' : 'تسجيل سلفة' }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('salary-advances.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">إلغاء</a>
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">حفظ</button>
    </div>
</div>

<div class="card max-w-3xl mx-auto p-6 space-y-4">
    <div>
        <label class="label">الموظف <span class="text-red-400">*</span></label>
        <select wire:model.live="employee_id" class="input select" @if($recordId) disabled @endif>
            <option value="">— اختر —</option>
            @foreach($employeeOptions as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
        @error('employee_id')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">المبلغ <span class="text-red-400">*</span></label>
            <input wire:model="amount" type="number" step="0.01" min="0.01" dir="ltr" class="input">
            @error('amount')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">العملة</label>
            <select wire:model="currency_code" class="input select">
                @foreach($currencyOptions as $c)
                <option value="{{ $c }}" dir="ltr">{{ $c }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">تاريخ الدفع</label>
            <input wire:model="paid_at" type="date" dir="ltr" class="input">
            @error('paid_at')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">الحالة</label>
            <select wire:model="status" class="input select">
                <option value="paid">مدفوعة</option>
                <option value="cancelled">ملغاة</option>
            </select>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">طريقة الدفع</label>
            <select wire:model.live="method" class="input select">
                <option value="cash">نقد</option>
                <option value="bank">بنك</option>
                <option value="check">شيك</option>
                <option value="transfer">تحويل</option>
            </select>
            @error('method')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        @if($canSelectPendingCheck && $isCheckPayment)
        <div class="sm:col-span-2">
            @include('livewire.partials.pending-check-select', [
                'pendingChecks' => $pendingChecks,
                'selectedCheck' => $selectedCheck,
                'currencyFilter' => $currencyFilter,
                'amountFilter' => $amountFilter,
            ])
        </div>
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
            <label class="label">مرجع بنكي</label>
            <input wire:model="bank_reference" type="text" dir="ltr" class="input">
        </div>
        @endif
    </div>

    <div>
        <label class="label">ملاحظات</label>
        <textarea wire:model="notes" rows="3" class="input"></textarea>
    </div>
</div>
</div>
