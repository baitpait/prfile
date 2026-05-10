<div>

{{-- ── شريط العنوان العلوي ── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="{{ route('invoices.index') }}" wire:navigate
           style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:1px solid #E2E4E9;background:#fff;color:#6B7280;text-decoration:none;transition:all .15s;"
           onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 style="font-size:18px;font-weight:800;color:#3D3D3D;line-height:1.2;">
                {{ $invoiceId ? 'تعديل الفاتورة' : 'فاتورة جديدة' }}
            </h1>
            @if($invoiceId)
            <p style="font-size:12px;color:#9CA3AF;margin-top:2px;">تعديل الفاتورة رقم #{{ $invoiceId }}</p>
            @endif
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('invoices.index') }}" wire:navigate class="btn btn-secondary" style="font-size:13px;text-decoration:none;">إلغاء</a>
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary" style="font-size:13px;min-width:120px;">
            <svg wire:loading wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">حفظ الفاتورة</span>
            <span wire:loading wire:target="save">جاري الحفظ...</span>
        </button>
    </div>
</div>

{{-- ── المحتوى الرئيسي (عمودان) ── --}}
<div style="display:flex;gap:20px;align-items:flex-start;">

    {{-- العمود الأيمن: معلومات الفاتورة + الإجمالي ── --}}
    <div style="width:300px;flex-shrink:0;display:flex;flex-direction:column;gap:16px;">

        {{-- معلومات الفاتورة --}}
        <div class="card" style="padding:20px;display:flex;flex-direction:column;gap:14px;">
            <p style="font-size:11px;font-weight:700;color:#9CA3AF;letter-spacing:.06em;text-transform:uppercase;">معلومات الفاتورة</p>

            <div>
                <label class="label">العميل <span class="text-red-400">*</span></label>
                <select wire:model="client_id" class="input select">
                    <option value="">— اختر العميل —</option>
                    @foreach($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->displayName() }}</option>
                    @endforeach
                </select>
                @error('client_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div style="display:flex;gap:10px;">
                <div style="flex:1;">
                    <label class="label">رقم الفاتورة</label>
                    <input wire:model="legacy_invoice_no" type="text" dir="ltr" class="input" placeholder="اختياري">
                </div>
                <div style="flex:1;">
                    <label class="label">الحالة <span class="text-red-400">*</span></label>
                    <select wire:model="status" class="input select">
                        <option value="draft">مسودة</option>
                        <option value="issued">صادرة</option>
                        <option value="cancelled">ملغاة</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="label">تاريخ الفاتورة <span class="text-red-400">*</span></label>
                <input wire:model="document_date" type="date" class="input">
                @error('document_date')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label">تاريخ الاستحقاق</label>
                <input wire:model="due_date" type="date" class="input">
                @error('due_date')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label">العملة <span class="text-red-400">*</span></label>
                <select wire:model="currency_code" class="input select">
                    <option value="ILS">ILS — شيكل إسرائيلي</option>
                    <option value="USD">USD — دولار أمريكي</option>
                    <option value="JOD">JOD — دينار أردني</option>
                    <option value="EUR">EUR — يورو</option>
                </select>
            </div>
        </div>

        {{-- ملخص الإجمالي --}}
        <div class="card" style="padding:20px;display:flex;flex-direction:column;gap:10px;">
            <p style="font-size:11px;font-weight:700;color:#9CA3AF;letter-spacing:.06em;text-transform:uppercase;">الإجمالي</p>

            <div style="display:flex;justify-content:space-between;font-size:13px;color:#6B7280;padding-bottom:8px;border-bottom:1px solid #F0F2F5;">
                <span>المجموع الفرعي</span>
                <span dir="ltr" style="font-weight:600;color:#3D3D3D;">{{ number_format($subtotal, 2) }} {{ $currency_code }}</span>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <label style="font-size:13px;color:#6B7280;white-space:nowrap;">الخصم</label>
                <input wire:model.live="discount_amount" type="number" step="0.01" min="0" dir="ltr"
                       placeholder="0.00" class="input" style="width:130px;padding:6px 10px;font-size:13px;text-align:center;">
            </div>

            @if(empty($lines))
            <div>
                <label class="label">الإجمالي الكلي <span class="text-red-400">*</span></label>
                <input wire:model.live="total_amount" type="number" step="0.01" min="0" dir="ltr"
                       placeholder="0.00" class="input" style="font-size:14px;padding:8px 12px;">
                @error('total_amount')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            @endif

            <div style="background:linear-gradient(135deg,#C9A227,#e0b83a);border-radius:12px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,.85);">الإجمالي</span>
                <span style="font-size:20px;font-weight:900;color:#fff;" dir="ltr">
                    {{ number_format((float)$total_amount, 2) }}
                    <span style="font-size:12px;font-weight:500;opacity:.8;">{{ $currency_code }}</span>
                </span>
            </div>
        </div>

        {{-- زر الحفظ --}}
        <button wire:click="save" wire:loading.attr="disabled"
                class="btn btn-primary" style="width:100%;font-size:14px;padding:12px;justify-content:center;">
            <svg wire:loading wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;display:inline;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                حفظ الفاتورة
            </span>
            <span wire:loading wire:target="save">جاري الحفظ...</span>
        </button>

    </div>

    {{-- العمود الأيسر: بنود الفاتورة ── --}}
    <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:16px;">

        <div class="card" style="padding:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <p style="font-size:13px;font-weight:700;color:#3D3D3D;">بنود الفاتورة</p>
                <button type="button" wire:click="addLine"
                        style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:#C9A227;background:transparent;border:none;cursor:pointer;padding:5px 10px;border-radius:8px;"
                        onmouseover="this.style.background='#FFFBEB'" onmouseout="this.style.background='transparent'">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    إضافة بند
                </button>
            </div>

            @if(empty($lines))
            <div style="border:2px dashed #E2E4E9;border-radius:12px;padding:60px 20px;text-align:center;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:44px;height:44px;color:#D1D5DB;margin:0 auto 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <p style="font-size:14px;color:#9CA3AF;margin-bottom:16px;">لم تُضف أي بنود بعد</p>
                <button type="button" wire:click="addLine"
                        style="background:#C9A227;color:#fff;border:none;border-radius:10px;padding:10px 28px;font-size:13px;font-weight:700;cursor:pointer;">
                    + أضف أول بند
                </button>
            </div>
            @else

            {{-- رأس الجدول --}}
            <div style="display:flex;background:#F9F9FB;border-radius:8px;margin-bottom:8px;font-size:11px;font-weight:600;color:#9CA3AF;">
                <div style="flex:2;padding:8px 12px;">البند / الوصف</div>
                <div style="width:130px;padding:8px 10px;text-align:center;">سعر الوحدة</div>
                <div style="width:100px;padding:8px 10px;text-align:center;">الكمية</div>
                <div style="width:120px;padding:8px 10px;text-align:left;" dir="ltr">المجموع</div>
                <div style="width:40px;"></div>
            </div>

            @foreach($lines as $i => $line)
            <div wire:key="line-{{ $i }}"
                 style="display:flex;align-items:flex-start;padding:8px 0;border-bottom:1px solid #F0F2F5;{{ $loop->last ? 'border-bottom:none;' : '' }}">

                <div style="flex:2;padding:0 8px;">
                    <input wire:model.live="lines.{{ $i }}.title"
                           type="text" placeholder="اسم البند *"
                           class="input" style="padding:7px 10px;font-size:13px;margin-bottom:4px;font-weight:600;">
                    <input wire:model="lines.{{ $i }}.description"
                           type="text" placeholder="وصف إضافي (اختياري)"
                           class="input" style="padding:6px 10px;font-size:12px;background:#F9F9FB;color:#6B7280;">
                    @error("lines.{$i}.title")<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div style="width:130px;padding:0 8px;">
                    <input wire:model.live="lines.{{ $i }}.unit_price"
                           type="number" step="0.01" min="0" dir="ltr" placeholder="0.00"
                           class="input" style="padding:7px 8px;font-size:13px;text-align:center;">
                    @error("lines.{$i}.unit_price")<p class="field-error" style="font-size:10px;">{{ $message }}</p>@enderror
                </div>

                <div style="width:100px;padding:0 8px;">
                    <input wire:model.live="lines.{{ $i }}.quantity"
                           type="number" step="1" min="0" dir="ltr" placeholder="1"
                           class="input" style="padding:7px 8px;font-size:13px;text-align:center;">
                    @error("lines.{$i}.quantity")<p class="field-error" style="font-size:10px;">{{ $message }}</p>@enderror
                </div>

                <div style="width:120px;padding:0 8px;display:flex;align-items:center;justify-content:flex-start;padding-top:7px;" dir="ltr">
                    <span style="font-weight:700;font-size:14px;color:#3D3D3D;">
                        {{ number_format((float)($line['line_total'] ?? 0), 2) }}
                    </span>
                </div>

                <div style="width:40px;display:flex;align-items:center;justify-content:center;padding-top:6px;">
                    <button type="button" wire:click="removeLine({{ $i }})"
                            style="width:26px;height:26px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;background:transparent;cursor:pointer;color:#D1D5DB;transition:all .15s;"
                            onmouseover="this.style.background='#FEE2E2';this.style.color='#EF4444'"
                            onmouseout="this.style.background='transparent';this.style.color='#D1D5DB'">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

            </div>
            @endforeach

            <button type="button" wire:click="addLine"
                    style="width:100%;margin-top:12px;padding:10px;border:1.5px dashed #E2E4E9;border-radius:10px;font-size:13px;font-weight:600;color:#C9A227;background:transparent;cursor:pointer;transition:all .15s;"
                    onmouseover="this.style.borderColor='#C9A227';this.style.background='#FFFBEB'"
                    onmouseout="this.style.borderColor='#E2E4E9';this.style.background='transparent'">
                + إضافة بند آخر
            </button>

            @endif
        </div>

        {{-- ملاحظات --}}
        <div class="card" style="padding:20px;">
            <label class="label" style="font-size:13px;font-weight:700;color:#3D3D3D;margin-bottom:8px;display:block;">ملاحظات</label>
            <textarea wire:model="notes" rows="4"
                      placeholder="أي ملاحظات أو تفاصيل إضافية..."
                      class="input" style="resize:vertical;font-size:13px;padding:10px 12px;"></textarea>
        </div>

    </div>

</div>

</div>
