<div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">الفواتير</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} فاتورة مسجّلة</p>
    </div>
    @if(auth()->user()->isAccountant())
    <button wire:click="openCreate" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        إضافة فاتورة
    </button>
    @endif
</div>

<div class="card px-4 py-3 mb-5 flex items-center gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
    <input wire:model.live.debounce.300ms="search" type="search"
           placeholder="بحث باسم العميل أو رقم الفاتورة..."
           class="flex-1 bg-transparent text-sm focus:outline-none placeholder:text-gray-300">
    @if($search)<button wire:click="$set('search','')" class="text-gray-300 hover:text-gray-500 text-lg leading-none">&times;</button>@endif
</div>

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#C9A227]/20 relative overflow-hidden">
        <div class="absolute inset-y-0 right-0 w-1/3 bg-[#C9A227] animate-pulse"></div>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>التاريخ</th><th>رقم الفاتورة</th><th>العميل</th><th>الحالة</th><th>المبلغ</th><th class="w-28"></th>
        </tr></thead>
        <tbody>
            @forelse($rows as $inv)
            <tr>
                <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $inv->document_date?->format('Y-m-d') ?? '—' }}</td>
                <td class="font-medium">{{ $inv->legacy_invoice_no ?? '#'.$inv->id }}</td>
                <td>{{ $inv->client?->displayName() ?? '—' }}</td>
                <td>
                    @php $s = $inv->status; @endphp
                    <span class="badge {{ $s==='issued' ? 'badge-green' : ($s==='draft' ? 'badge-yellow' : 'badge-red') }}">
                        {{ $s==='issued' ? 'صادرة' : ($s==='draft' ? 'مسودة' : 'ملغاة') }}
                    </span>
                </td>
                <td class="font-mono font-semibold text-xs" dir="ltr">
                    {{ number_format((float)$inv->total_amount,2) }}
                    <span class="text-gray-400 font-normal">{{ $inv->currency_code }}</span>
                </td>
                <td>
                    <div class="flex items-center gap-1 justify-end">
                        <button wire:click="openView({{ $inv->id }})" class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50">عرض</button>
                        <a href="{{ route('invoices.print', $inv->id) }}" target="_blank" class="btn btn-ghost py-1 px-2 text-xs text-[#C9A227] hover:bg-amber-50">طباعة</a>
                        @if(auth()->user()->isAccountant())
                        <button wire:click="openEdit({{ $inv->id }})" class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50">تعديل</button>
                        @endif
                        @if(auth()->user()->isManager())
                        <button wire:click="confirmDelete({{ $inv->id }})" class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">حذف</button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6">
                <div class="text-center py-16 text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm">{{ $search ? 'لا توجد نتائج' : 'لا توجد فواتير بعد' }}</p>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($rows->hasPages())<div class="mt-5">{{ $rows->links() }}</div>@endif

{{-- ══ نافذة العرض التفصيلي ══ --}}
@if($viewingId !== null)
<div wire:key="view-{{ $viewingId }}"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="closeView"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-lg mx-0 sm:mx-4 z-10 max-h-[90vh] overflow-y-auto">
        @if($viewingRecord)
        @php $inv = $viewingRecord; $s = $inv->status; @endphp
        <div class="p-6">
            <div class="flex items-start justify-between mb-5">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-lg font-bold text-[#3D3D3D]">{{ $inv->legacy_invoice_no ?? 'فاتورة #'.$inv->id }}</h2>
                        <span class="badge {{ $s==='issued' ? 'badge-green' : ($s==='draft' ? 'badge-yellow' : 'badge-red') }}">
                            {{ $s==='issued' ? 'صادرة' : ($s==='draft' ? 'مسودة' : 'ملغاة') }}
                        </span>
                    </div>
                    <p class="text-sm text-[#C9A227] font-semibold">{{ $inv->client?->displayName() ?? '—' }}</p>
                </div>
                <button wire:click="closeView" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <dl class="divide-y divide-[#E2E4E9]">
                <div class="flex justify-between py-3">
                    <dt class="text-sm text-gray-500">تاريخ الفاتورة</dt>
                    <dd class="text-sm font-medium text-[#3D3D3D]" dir="ltr">{{ $inv->document_date?->format('Y-m-d') ?? '—' }}</dd>
                </div>
                @if($inv->due_date)
                <div class="flex justify-between py-3">
                    <dt class="text-sm text-gray-500">تاريخ الاستحقاق</dt>
                    <dd class="text-sm font-medium {{ $inv->due_date->isPast() && $s === 'issued' ? 'text-red-500' : 'text-[#3D3D3D]' }}" dir="ltr">{{ $inv->due_date->format('Y-m-d') }}</dd>
                </div>
                @endif
                <div class="flex justify-between py-3">
                    <dt class="text-sm text-gray-500">العميل</dt>
                    <dd class="text-sm font-medium text-[#3D3D3D]">{{ $inv->client?->displayName() ?? '—' }}</dd>
                </div>
                @if($inv->notes)
                <div class="py-3">
                    <dt class="text-sm text-gray-500 mb-1">ملاحظات</dt>
                    <dd class="text-sm text-[#3D3D3D] bg-amber-50 rounded-lg p-2">{{ $inv->notes }}</dd>
                </div>
                @endif
            </dl>

            {{-- بنود الفاتورة --}}
            @if($inv->lines->isNotEmpty())
            <div class="mt-4 mb-2">
                <p class="text-xs font-semibold text-[#C9A227] mb-2 uppercase tracking-wide">بنود الفاتورة</p>
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#3D3D3D] text-white">
                            <th class="text-right px-3 py-2 rounded-r-md">البند</th>
                            <th class="text-center px-2 py-2">الكمية</th>
                            <th class="text-left px-3 py-2 rounded-l-md" dir="ltr">المجموع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inv->lines as $line)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                            <td class="px-3 py-2 border-b border-[#E2E4E9]">
                                <div class="font-semibold text-[#3D3D3D]">{{ $line->title }}</div>
                                @if($line->description)<div class="text-gray-400 text-[10px] mt-0.5">{{ $line->description }}</div>@endif
                            </td>
                            <td class="px-2 py-2 text-center border-b border-[#E2E4E9] text-gray-500">{{ rtrim(rtrim(number_format((float)$line->quantity, 2), '0'), '.') }}</td>
                            <td class="px-3 py-2 border-b border-[#E2E4E9] font-mono font-semibold text-[#3D3D3D]" dir="ltr">{{ number_format((float)$line->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- الإجمالي --}}
            <div class="mt-3 pt-3 border-t border-[#E2E4E9] space-y-1">
                @if($inv->discount_amount && $inv->discount_amount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">الخصم</span>
                    <span class="text-orange-500 font-medium" dir="ltr">- {{ number_format((float)$inv->discount_amount, 2) }} {{ $inv->currency_code }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-sm font-bold text-[#3D3D3D]">الإجمالي</span>
                    <span class="text-base font-bold text-[#3D3D3D]" dir="ltr">
                        {{ number_format((float)$inv->total_amount, 2) }}
                        <span class="text-xs font-normal text-gray-400">{{ $inv->currency_code }}</span>
                    </span>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-[#E2E4E9] mt-4">
                <button wire:click="closeView" class="btn btn-secondary text-xs">إغلاق</button>
                <a href="{{ route('invoices.print', $inv->id) }}" target="_blank" class="btn text-xs bg-amber-50 text-[#C9A227] border border-[#C9A227]/30 hover:bg-amber-100">طباعة</a>
                @if(auth()->user()->isAccountant())
                <button wire:click="openEdit({{ $inv->id }})" class="btn btn-primary text-xs">تعديل</button>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- مودال --}}
@if($showModal)
<div wire:key="form-{{ $editingId ?? 'new' }}"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="closeModal"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-lg mx-0 sm:mx-4 z-10 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold text-[#3D3D3D]">{{ $editingId ? 'تعديل الفاتورة' : 'إضافة فاتورة جديدة' }}</h2>
                <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 form-group">
                    <label class="label">العميل <span class="text-red-400">*</span></label>
                    <select wire:model="client_id" class="input select">
                        <option value="">— اختر العميل —</option>
                        @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->displayName() }}</option>@endforeach
                    </select>
                    @error('client_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label class="label">رقم الفاتورة</label>
                    <input wire:model="legacy_invoice_no" type="text" dir="ltr" class="input">
                </div>
                <div class="form-group">
                    <label class="label">الحالة <span class="text-red-400">*</span></label>
                    <select wire:model="status" class="input select">
                        <option value="draft">مسودة</option>
                        <option value="issued">صادرة</option>
                        <option value="cancelled">ملغاة</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label">تاريخ الفاتورة <span class="text-red-400">*</span></label>
                    <input wire:model="document_date" type="date" class="input">
                    @error('document_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label class="label">تاريخ الاستحقاق</label>
                    <input wire:model="due_date" type="date" class="input">
                    @error('due_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label class="label">المبلغ الإجمالي <span class="text-red-400">*</span></label>
                    <input wire:model="total_amount" type="number" step="0.01" min="0" dir="ltr" class="input">
                    @error('total_amount')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label class="label">العملة <span class="text-red-400">*</span></label>
                    <select wire:model="currency_code" class="input select">
                        <option value="ILS">ILS — شيكل</option>
                        <option value="USD">USD — دولار</option>
                        <option value="JOD">JOD — دينار</option>
                        <option value="EUR">EUR — يورو</option>
                    </select>
                </div>
                <div class="col-span-2 form-group"><label class="label">ملاحظات</label><textarea wire:model="notes" rows="2" class="input"></textarea></div>
            </div>
            <div class="flex justify-end gap-2 mt-2 pt-4 border-t border-[#E2E4E9]">
                <button wire:click="closeModal" class="btn btn-secondary">إلغاء</button>
                <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">
                    <svg wire:loading wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                    <span wire:loading.remove wire:target="save">حفظ</span>
                    <span wire:loading wire:target="save">جاري الحفظ...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($confirmDeleteId !== null)
<div wire:key="delete-{{ $confirmDeleteId }}"
     class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="text-base font-bold text-center mb-1">حذف الفاتورة</h3>
        <p class="text-sm text-gray-400 text-center mb-5">هل أنت متأكد؟ لا يمكن التراجع.</p>
        <div class="flex gap-2">
            <button wire:click="cancelDelete" class="btn btn-secondary flex-1">إلغاء</button>
            <button wire:click="delete" class="btn btn-danger flex-1">حذف</button>
        </div>
    </div>
</div>
@endif

</div>
