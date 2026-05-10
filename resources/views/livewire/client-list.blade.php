<div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">العملاء</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} عميل مسجّل</p>
    </div>
    @if(auth()->user()->isAccountant())
    <button wire:click="openCreate" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        إضافة عميل
    </button>
    @endif
</div>

<div class="card px-4 py-3 mb-5 flex items-center gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
    </svg>
    <input wire:model.live.debounce.300ms="search"
           type="search"
           placeholder="بحث بالاسم، البريد، الهاتف، المدينة..."
           class="flex-1 bg-transparent text-sm focus:outline-none placeholder:text-gray-300">
    @if($search)
    <button wire:click="$set('search','')" class="text-gray-300 hover:text-gray-500 transition text-lg leading-none">&times;</button>
    @endif
</div>

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#C9A227]/20 relative overflow-hidden">
        <div class="absolute inset-y-0 right-0 w-1/3 bg-[#C9A227] animate-pulse"></div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>الاسم</th>
                <th>البريد</th>
                <th>الهاتف</th>
                <th>المدينة</th>
                <th class="w-36"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $client)
            <tr>
                <td class="font-semibold">{{ $client->displayName() }}</td>
                <td class="text-gray-500">{{ $client->email ?? '—' }}</td>
                <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $client->phone_primary ?? '—' }}</td>
                <td class="text-gray-500">{{ $client->city ?? '—' }}</td>
                <td>
                    <div class="flex items-center gap-1 justify-end">
                        <button wire:click="openView({{ $client->id }})"
                                class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50">عرض</button>
                        @if(auth()->user()->isAccountant())
                        <button wire:click="openEdit({{ $client->id }})"
                                class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50">تعديل</button>
                        @endif
                        @if(auth()->user()->isManager())
                        <button wire:click="confirmDelete({{ $client->id }})"
                                class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">حذف</button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5">
                    <div class="text-center py-16 text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm">{{ $search ? 'لا توجد نتائج للبحث' : 'لا يوجد عملاء بعد' }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($rows->hasPages())
<div class="mt-5">{{ $rows->links() }}</div>
@endif

{{-- نافذة العرض التفصيلي --}}
@if($viewingId !== null)
<div wire:key="view-{{ $viewingId }}"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="closeView"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-2xl mx-0 sm:mx-4 z-10 max-h-[92vh] overflow-y-auto">
        @if($viewingRecord)
        @php
            $c = $viewingRecord;

            $allInvoices   = $c->invoices;
            $issuedInvs    = $allInvoices->where('status','issued');
            $draftInvs     = $allInvoices->where('status','draft');
            $cancelledInvs = $allInvoices->where('status','cancelled');
            $allPayments   = $c->payments;

            $invByCur = $issuedInvs->groupBy('currency_code');
            $payByCur = $allPayments->groupBy('currency_code');
            $allCur   = $invByCur->keys()->merge($payByCur->keys())->unique();

            $lastInvoiceDate  = $allInvoices->first()?->document_date;
            $lastPaymentDate  = $allPayments->first()?->paid_at;
        @endphp

        {{-- ── رأس النافذة ── --}}
        <div class="sticky top-0 bg-white z-10 px-6 pt-6 pb-4 border-b border-[#E2E4E9]">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-xl shrink-0 shadow">
                        {{ mb_substr($c->displayName(), 0, 1) }}
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-[#3D3D3D] leading-tight">{{ $c->displayName() }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            @if($c->business_name && ($c->first_name || $c->last_name))
                                {{ trim($c->first_name.' '.$c->last_name) }} •
                            @endif
                            {{ $c->city ?? '' }}{{ $c->city && $c->country_code ? ' · '.$c->country_code : $c->country_code ?? '' }}
                        </p>
                    </div>
                </div>
                <button wire:click="closeView" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition shrink-0 mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div class="p-6 space-y-5">

            {{-- ── معلومات التواصل ── --}}
            <div class="grid grid-cols-2 gap-3 text-sm">
                @if($c->email)
                <div class="flex items-center gap-2 col-span-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#C9A227] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span dir="ltr" class="text-[#3D3D3D]">{{ $c->email }}</span>
                </div>
                @endif
                @if($c->phone_primary)
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#C9A227] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span dir="ltr" class="text-[#3D3D3D]">{{ $c->phone_primary }}</span>
                </div>
                @endif
                @if($c->phone_secondary)
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span dir="ltr" class="text-gray-500">{{ $c->phone_secondary }}</span>
                </div>
                @endif
                @if(!$c->email && !$c->phone_primary)
                <p class="text-gray-300 col-span-2 text-xs">لا توجد معلومات تواصل</p>
                @endif
            </div>

            {{-- ── إحصاء سريع ── --}}
            <div class="grid grid-cols-4 gap-2 text-center">
                <div class="bg-[#F9F9FB] rounded-xl p-3">
                    <p class="text-2xl font-bold text-[#3D3D3D]">{{ $allInvoices->count() }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">فاتورة</p>
                </div>
                <div class="bg-green-50 rounded-xl p-3">
                    <p class="text-2xl font-bold text-green-600">{{ $issuedInvs->count() }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">صادرة</p>
                </div>
                <div class="bg-amber-50 rounded-xl p-3">
                    <p class="text-2xl font-bold text-amber-600">{{ $draftInvs->count() }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">مسودة</p>
                </div>
                <div class="bg-blue-50 rounded-xl p-3">
                    <p class="text-2xl font-bold text-blue-600">{{ $allPayments->count() }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">دفعة</p>
                </div>
            </div>

            {{-- ── الملخص المالي لكل عملة ── --}}
            @if($allCur->isNotEmpty())
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">الملخص المالي</p>
                <div class="space-y-3">
                @foreach($allCur as $cur)
                @php
                    $invoiced  = (float)($invByCur->get($cur)?->sum('total_amount') ?? 0);
                    $paid      = (float)($payByCur->get($cur)?->sum('amount') ?? 0);
                    $balance   = $invoiced - $paid;
                    $pct       = $invoiced > 0 ? min(100, round($paid / $invoiced * 100)) : 0;
                @endphp
                <div class="border border-[#E2E4E9] rounded-2xl overflow-hidden">
                    {{-- رأس العملة --}}
                    <div class="bg-[#F9F9FB] px-4 py-2 flex items-center justify-between">
                        <span class="text-xs font-bold text-[#3D3D3D]" dir="ltr">{{ $cur }}</span>
                        <span class="badge {{ $balance <= 0 ? 'badge-green' : ($balance < $invoiced * 0.3 ? 'badge-yellow' : 'badge-red') }}">
                            {{ $balance <= 0 ? 'محصّل بالكامل' : 'رصيد مستحق' }}
                        </span>
                    </div>
                    {{-- الأرقام --}}
                    <div class="grid grid-cols-3 divide-x divide-x-reverse divide-[#E2E4E9] text-center">
                        <div class="p-3">
                            <p class="text-[10px] text-gray-400 mb-1">إجمالي الفواتير</p>
                            <p class="text-sm font-bold text-[#3D3D3D]" dir="ltr">{{ number_format($invoiced, 2) }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-[10px] text-gray-400 mb-1">إجمالي المدفوع</p>
                            <p class="text-sm font-bold text-green-600" dir="ltr">{{ number_format($paid, 2) }}</p>
                        </div>
                        <div class="p-3">
                            <p class="text-[10px] text-gray-400 mb-1">الرصيد المستحق</p>
                            <p class="text-sm font-bold {{ $balance > 0 ? 'text-red-500' : 'text-green-600' }}" dir="ltr">
                                {{ $balance > 0 ? number_format($balance, 2) : '0.00' }}
                            </p>
                        </div>
                    </div>
                    {{-- شريط التحصيل --}}
                    @if($invoiced > 0)
                    <div class="px-4 pb-3">
                        <div class="flex justify-between text-[10px] text-gray-400 mb-1">
                            <span>نسبة التحصيل</span>
                            <span dir="ltr">{{ $pct }}%</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all {{ $pct >= 100 ? 'bg-green-500' : ($pct >= 60 ? 'bg-[#C9A227]' : 'bg-red-400') }}"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- ── آخر الفواتير ── --}}
            @if($allInvoices->isNotEmpty())
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">آخر الفواتير</p>
                    @if($lastInvoiceDate)
                    <span class="text-[10px] text-gray-300" dir="ltr">{{ $lastInvoiceDate->format('Y-m-d') }}</span>
                    @endif
                </div>
                <div class="border border-[#E2E4E9] rounded-xl overflow-hidden">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-[#F9F9FB] text-gray-400">
                                <th class="text-right px-3 py-2 font-semibold">رقم / تاريخ</th>
                                <th class="text-center px-2 py-2 font-semibold">الحالة</th>
                                <th class="text-left px-3 py-2 font-semibold" dir="ltr">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allInvoices->take(6) as $inv)
                            @php $s = $inv->status; @endphp
                            <tr class="border-t border-[#F0F2F5] {{ $loop->even ? 'bg-[#FAFAFA]' : '' }}">
                                <td class="px-3 py-2">
                                    <p class="font-semibold text-[#3D3D3D]">{{ $inv->legacy_invoice_no ?? '#'.$inv->id }}</p>
                                    <p class="text-[10px] text-gray-400" dir="ltr">{{ $inv->document_date?->format('Y-m-d') }}</p>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <span class="badge {{ $s==='issued' ? 'badge-green' : ($s==='draft' ? 'badge-yellow' : 'badge-red') }}">
                                        {{ $s==='issued' ? 'صادرة' : ($s==='draft' ? 'مسودة' : 'ملغاة') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-mono font-semibold text-[#3D3D3D]" dir="ltr">
                                    {{ number_format((float)$inv->total_amount, 2) }}
                                    <span class="text-[10px] text-gray-400">{{ $inv->currency_code }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ── آخر الدفعات ── --}}
            @if($allPayments->isNotEmpty())
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">آخر الدفعات</p>
                    @if($lastPaymentDate)
                    <span class="text-[10px] text-gray-300" dir="ltr">{{ $lastPaymentDate->format('Y-m-d') }}</span>
                    @endif
                </div>
                <div class="border border-[#E2E4E9] rounded-xl overflow-hidden">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-[#F9F9FB] text-gray-400">
                                <th class="text-right px-3 py-2 font-semibold">التاريخ</th>
                                <th class="text-center px-2 py-2 font-semibold">الطريقة</th>
                                <th class="text-left px-3 py-2 font-semibold" dir="ltr">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allPayments->take(5) as $pay)
                            <tr class="border-t border-[#F0F2F5] {{ $loop->even ? 'bg-[#FAFAFA]' : '' }}">
                                <td class="px-3 py-2 font-mono text-gray-500" dir="ltr">{{ $pay->paid_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-2 py-2 text-center">
                                    @php $m = $pay->method ?? ''; @endphp
                                    <span class="badge badge-blue">
                                        {{ $m==='cash' ? 'نقداً' : ($m==='bank' ? 'بنك' : ($m==='check' ? 'شيك' : ($m==='transfer' ? 'تحويل' : $m))) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-mono font-semibold text-green-600" dir="ltr">
                                    {{ number_format((float)$pay->amount, 2) }}
                                    <span class="text-[10px] text-gray-400">{{ $pay->currency_code }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ── ملاحظات ── --}}
            @if($c->notes)
            <div class="bg-amber-50 border border-amber-100 rounded-xl p-3">
                <p class="text-xs font-bold text-amber-600 mb-1">ملاحظات</p>
                <p class="text-sm text-gray-700">{{ $c->notes }}</p>
            </div>
            @endif

            {{-- ── أزرار السفلى ── --}}
            <div class="flex items-center justify-between pt-2 border-t border-[#E2E4E9]">
                <a href="{{ route('clients.statement', $c) }}" class="btn btn-secondary text-xs gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    كشف حساب
                </a>
                <div class="flex gap-2">
                    <button wire:click="closeView" class="btn btn-secondary text-xs">إغلاق</button>
                    @if(auth()->user()->isAccountant())
                    <button wire:click="openEdit({{ $c->id }})" class="btn btn-primary text-xs">تعديل</button>
                    @endif
                </div>
            </div>

        </div>
        @endif
    </div>
</div>
@endif

{{-- مودال الإضافة / التعديل --}}
@if($showModal)
<div wire:key="form-{{ $editingId ?? 'new' }}"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="closeModal"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-lg mx-0 sm:mx-4 z-10 max-h-[90vh] overflow-y-auto">

            <div class="p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-bold text-[#3D3D3D]">
                        {{ $editingId ? 'تعديل بيانات العميل' : 'إضافة عميل جديد' }}
                    </h2>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 form-group">
                        <label class="label">اسم الشركة</label>
                        <input wire:model="business_name" type="text" class="input" placeholder="اختياري إن أدخلت الاسم الشخصي">
                        @error('business_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="label">الاسم الأول</label>
                        <input wire:model="first_name" type="text" class="input">
                    </div>
                    <div class="form-group">
                        <label class="label">الاسم الأخير</label>
                        <input wire:model="last_name" type="text" class="input">
                    </div>
                    <div class="form-group">
                        <label class="label">البريد الإلكتروني</label>
                        <input wire:model="email" type="email" dir="ltr" class="input">
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="label">الهاتف الرئيسي</label>
                        <input wire:model="phone_primary" type="tel" dir="ltr" class="input">
                        @error('phone_primary')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="label">الهاتف الثانوي</label>
                        <input wire:model="phone_secondary" type="tel" dir="ltr" class="input">
                    </div>
                    <div class="form-group">
                        <label class="label">المدينة</label>
                        <input wire:model="city" type="text" class="input">
                    </div>
                    <div class="col-span-2 form-group">
                        <label class="label">ملاحظات</label>
                        <textarea wire:model="notes" rows="2" class="input"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-2 pt-4 border-t border-[#E2E4E9]">
                    <button wire:click="closeModal" class="btn btn-secondary">إلغاء</button>
                    <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">
                        <svg wire:loading wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="save">حفظ</span>
                        <span wire:loading wire:target="save">جاري الحفظ...</span>
                    </button>
                </div>
            </div>
    </div>
</div>
@endif

{{-- نافذة تأكيد الحذف --}}
@if($confirmDeleteId !== null)
<div wire:key="delete-{{ $confirmDeleteId }}"
     class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
    <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
    </div>
    <h3 class="text-base font-bold text-center text-[#3D3D3D] mb-1">حذف العميل</h3>
    <p class="text-sm text-gray-400 text-center mb-5">هل أنت متأكد؟ يمكن استعادة السجل لاحقاً.</p>
    <div class="flex gap-2">
        <button wire:click="cancelDelete" class="btn btn-secondary flex-1">إلغاء</button>
        <button wire:click="delete" class="btn btn-danger flex-1">حذف</button>
    </div>
</div>
</div>
@endif

</div>
