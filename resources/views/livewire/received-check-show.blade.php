<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('received-checks.index') }}" wire:navigate
           class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-[#E2E4E9] text-gray-400 hover:text-[#3D3D3D]"
           style="text-decoration:none;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#3D3D3D]">تفاصيل الشيك</h1>
            <p class="text-sm text-gray-400">{{ $check->client?->displayName() }}</p>
        </div>
    </div>

    <div class="card p-6">
        <div class="flex items-start justify-between mb-6 pb-6 border-b border-[#E2E4E9]">
            <div>
                <p class="text-2xl font-bold text-[#3D3D3D]" dir="ltr">
                    {{ number_format((float) $check->amount, 2) }}
                    <span class="text-sm font-normal text-gray-400">{{ $check->currency_code }}</span>
                </p>
                <p class="text-sm text-gray-500 mt-1">رقم {{ $check->check_number }} — {{ $check->bank_name }}</p>
            </div>
            <span class="badge {{ \App\Services\Finance\ReceivedCheckStatus::badgeClass($check->status) }} text-sm px-3 py-1">
                {{ $check->statusLabel() }}
            </span>
        </div>

        <dl class="divide-y divide-[#E2E4E9]">
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">صاحب الشيك</dt>
                <dd class="text-sm font-semibold">{{ $check->drawer_name }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">تاريخ الاستحقاق</dt>
                <dd class="text-sm font-medium" dir="ltr">{{ $check->due_date?->format('Y-m-d') }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">العميل (مصدر الاستلام)</dt>
                <dd class="text-sm font-semibold">{{ $check->client?->displayName() }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">دفعة العميل</dt>
                <dd>
                    @if($check->client_payment_id)
                    @if($paymentReversed)
                    <span class="text-sm text-red-600 font-semibold">#{{ $check->client_payment_id }} — مُعكوسة</span>
                    @else
                    <a href="{{ route('payments.show', $check->client_payment_id) }}" wire:navigate
                       class="text-sm text-[#C9A227] font-semibold" style="text-decoration:none;">
                        #{{ $check->client_payment_id }}
                    </a>
                    @endif
                    @else
                    —
                    @endif
                </dd>
            </div>
            @if($check->image_path)
            <div class="py-3">
                <dt class="text-sm text-gray-500 mb-2">صورة الشيك</dt>
                <dd>
                    <a href="{{ Storage::disk('public')->url($check->image_path) }}" target="_blank"
                       class="text-sm text-[#C9A227] font-medium">فتح الصورة</a>
                </dd>
            </div>
            @endif
            @if($check->notes)
            <div class="py-3">
                <dt class="text-sm text-gray-500 mb-1">ملاحظات</dt>
                <dd class="text-sm bg-amber-50 rounded-lg p-3">{{ $check->notes }}</dd>
            </div>
            @endif
            @if($check->status === \App\Services\Finance\ReceivedCheckStatus::NOT_CLEARED)
            <div class="py-3">
                @if($paymentReversed)
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700">
                    تم <strong>عكس دفعة العميل</strong> — رصيد العميل يعكس هذا الشيك في الصندوق المالي.
                </div>
                @else
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800">
                    هذا الشيك «لم يُصرف». يمكنك عكس دفعة العميل بضغطة واحدة لاستعادة رصيد العميل في الصندوق المالي.
                </div>
                @if(auth()->user()->isAccountant())
                <button type="button" wire:click="reverseClientPayment"
                        wire:confirm="تأكيد عكس دفعة العميل؟ سيتم إلغاء الدفعة المرتبطة."
                        class="btn btn-secondary text-red-700 mt-3">
                    عكس دفعة العميل
                </button>
                @endif
                @endif
            </div>
            @endif
            @if($check->isEndorsed())
            @if($check->endorsed_supplier_id)
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">حُوّل لمورد</dt>
                <dd class="text-sm font-semibold">{{ $check->endorsedSupplier?->displayName() ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">دفعة المورد</dt>
                <dd>
                    @if($check->supplier_payment_id)
                    <a href="{{ route('supplier-payments.show', $check->supplier_payment_id) }}" wire:navigate
                       class="text-sm text-[#C9A227] font-semibold" style="text-decoration:none;">
                        #{{ $check->supplier_payment_id }}
                    </a>
                    @else
                    —
                    @endif
                </dd>
            </div>
            @endif
            @if($check->endorsed_employee_id)
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">حُوّل لموظف</dt>
                <dd class="text-sm font-semibold">{{ $check->endorsedEmployee?->displayName() ?? '—' }}</dd>
            </div>
            @if($check->salary_payment_id)
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">سجل الراتب</dt>
                <dd>
                    <a href="{{ route('salary-payments.show', $check->salary_payment_id) }}" wire:navigate
                       class="text-sm text-[#C9A227] font-semibold" style="text-decoration:none;">
                        #{{ $check->salary_payment_id }}
                    </a>
                </dd>
            </div>
            @elseif($check->salary_advance_id)
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">سلفة</dt>
                <dd>
                    <a href="{{ route('salary-advances.show', $check->salary_advance_id) }}" wire:navigate
                       class="text-sm text-[#C9A227] font-semibold" style="text-decoration:none;">
                        #{{ $check->salary_advance_id }}
                    </a>
                </dd>
            </div>
            @endif
            @endif
            @if($check->endorsed_at)
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">تاريخ التظهير</dt>
                <dd class="text-sm" dir="ltr">{{ $check->endorsed_at->format('Y-m-d H:i') }}</dd>
            </div>
            @endif
            @endif
        </dl>

        @if($check->isPending() && auth()->user()->isAccountant())
        <div class="flex flex-wrap gap-2 pt-5 mt-2 border-t border-[#E2E4E9]">
            <button type="button" wire:click="markCleared" class="btn btn-primary">تسجيل: صُرف</button>
            <button type="button" wire:click="markNotCleared" class="btn btn-secondary text-red-700">تسجيل: لم يُصرف</button>
            @if(!$showEndorseForm && !$showEndorseEmployeeForm)
            <button type="button" wire:click="openEndorseForm" class="btn btn-secondary text-[#7C3AED]">تحويل لمورد</button>
            <button type="button" wire:click="openEndorseEmployeeForm" class="btn btn-secondary text-[#2563EB]">تحويل لموظف</button>
            @endif
        </div>
        @endif

        @if($showEndorseForm && $check->isPending() && auth()->user()->isAccountant())
        <div class="mt-6 pt-5 border-t border-[#E2E4E9]">
            <h3 class="text-sm font-bold text-[#3D3D3D] mb-4">تظهير الشيك لمورد</h3>
            <p class="text-xs text-gray-500 mb-4">
                سيتم إنشاء دفعة مورد بمبلغ {{ number_format((float) $check->amount, 2) }} {{ $check->currency_code }} وطريقة «شيك».
            </p>

            <div class="space-y-4">
                <div>
                    <label class="label">المورد <span class="text-red-400">*</span></label>
                    <select wire:model.live="endorse_supplier_id" class="input select">
                        <option value="">— اختر المورد —</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->displayName() }}</option>
                        @endforeach
                    </select>
                    @error('endorse_supplier_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label">ربط الدفعة</label>
                    <div class="flex flex-col gap-2 mt-1">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" wire:model.live="endorse_allocation_mode" value="account">
                            عموم حساب المورد
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" wire:model.live="endorse_allocation_mode" value="purchase_order">
                            على أمر شراء (بنفس مبلغ الشيك)
                        </label>
                    </div>
                </div>

                @if($endorse_allocation_mode === 'purchase_order')
                <div>
                    <label class="label">أمر الشراء</label>
                    <select wire:model="endorse_purchase_order_id" class="input select">
                        <option value="">— اختر أمر شراء —</option>
                        @foreach($openPurchaseOrders as $po)
                            @php $poNo = $po->legacy_po_no ?? '#'.$po->id; @endphp
                            <option value="{{ $po->id }}">
                                {{ $poNo }} — {{ $po->document_date?->format('Y-m-d') }} — {{ number_format((float) $po->total_amount, 2) }}
                            </option>
                        @endforeach
                    </select>
                    @error('endorse_purchase_order_id')<p class="field-error">{{ $message }}</p>@enderror
                    @if($endorse_supplier_id !== '' && $openPurchaseOrders->isEmpty())
                        <p class="text-xs text-amber-700 mt-1">لا توجد أوامر شراء بنفس مبلغ الشيك وغير مربوطة بدفعة.</p>
                    @endif
                </div>
                @endif

                <div class="flex gap-2">
                    <button type="button" wire:click="endorseToSupplier" class="btn btn-primary">تأكيد التظهير</button>
                    <button type="button" wire:click="cancelEndorseForm" class="btn btn-secondary">إلغاء</button>
                </div>
            </div>
        </div>
        @endif

        @if($showEndorseEmployeeForm && $check->isPending() && auth()->user()->isAccountant())
        <div class="mt-6 pt-5 border-t border-[#E2E4E9]">
            <h3 class="text-sm font-bold text-[#3D3D3D] mb-4">تظهير الشيك لموظف</h3>
            <p class="text-xs text-gray-500 mb-4">
                سيتم إنشاء {{ $endorse_employee_mode === 'advance' ? 'سلفة' : 'راتب مدفوع' }}
                بمبلغ {{ number_format((float) $check->amount, 2) }} {{ $check->currency_code }} وطريقة «شيك».
            </p>

            <div class="space-y-4">
                <div>
                    <label class="label">الموظف <span class="text-red-400">*</span></label>
                    <select wire:model="endorse_employee_id" class="input select">
                        <option value="">— اختر الموظف —</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->displayName() }}</option>
                        @endforeach
                    </select>
                    @error('endorse_employee_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label">نوع الدفع</label>
                    <div class="flex flex-col gap-2 mt-1">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" wire:model.live="endorse_employee_mode" value="advance">
                            سلفة
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" wire:model.live="endorse_employee_mode" value="salary">
                            راتب شهر
                        </label>
                    </div>
                </div>

                @if($endorse_employee_mode === 'salary')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">سنة الراتب</label>
                        <input type="number" wire:model="endorse_period_year" min="2000" max="2100" dir="ltr" class="input">
                        @error('endorse_period_year')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">شهر الراتب</label>
                        <select wire:model="endorse_period_month" class="input select">
                            @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ sprintf('%02d', $m) }}</option>
                            @endfor
                        </select>
                        @error('endorse_period_month')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                @endif

                <div class="flex gap-2">
                    <button type="button" wire:click="endorseToEmployee" class="btn btn-primary">تأكيد التظهير</button>
                    <button type="button" wire:click="cancelEndorseEmployeeForm" class="btn btn-secondary">إلغاء</button>
                </div>
            </div>
        </div>
        @endif
    </div>

    @if($timeline->isNotEmpty())
    <div class="card p-6 mt-5">
        <h2 class="text-sm font-bold text-[#3D3D3D] mb-4">سجل الحركة</h2>
        <ol class="relative border-r-2 border-[#E2E4E9] mr-3 space-y-6">
            @foreach($timeline as $event)
            @php
                $toneClass = match($event['tone']) {
                    'cleared' => 'bg-green-100 border-green-300 text-green-800',
                    'not_cleared' => 'bg-red-100 border-red-300 text-red-800',
                    'endorsed' => 'bg-purple-100 border-purple-300 text-purple-800',
                    'reversed' => 'bg-gray-100 border-gray-300 text-gray-800',
                    default => 'bg-amber-50 border-amber-200 text-amber-900',
                };
            @endphp
            <li class="mr-6 relative">
                <span class="absolute -right-[9px] top-1 w-4 h-4 rounded-full border-2 border-white {{ str_contains($toneClass, 'green') ? 'bg-green-500' : (str_contains($toneClass, 'red') ? 'bg-red-500' : (str_contains($toneClass, 'purple') ? 'bg-purple-500' : (str_contains($toneClass, 'gray') ? 'bg-gray-500' : 'bg-[#C9A227]'))) }}"></span>
                <div class="rounded-lg border p-3 {{ $toneClass }}">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-sm font-bold">{{ $event['label'] }}</p>
                        <time class="text-xs opacity-70" dir="ltr">{{ $event['date']->format('Y-m-d H:i') }}</time>
                    </div>
                    <p class="text-xs">{{ $event['detail'] }}</p>
                </div>
            </li>
            @endforeach
        </ol>
    </div>
    @endif
</div>
