<x-layouts.app title="لوحة التحكم">
@php
    $clientCount   = \App\Models\Client::count();
    $supplierCount = \App\Models\Supplier::count();
    $invoiceOpen   = \App\Models\Invoice::where('status','issued')->count();
    $invoiceTotal  = \App\Models\Invoice::count();
    $expenseCount  = \App\Models\Expense::count();
    $paymentCount  = \App\Models\ClientPayment::count();

    $invoicedByC = \App\Models\Invoice::where('status','issued')
        ->selectRaw('currency_code, sum(total_amount) as total')
        ->groupBy('currency_code')->pluck('total','currency_code');

    $paidByC = \App\Models\ClientPayment::selectRaw('currency_code, sum(amount) as total')
        ->groupBy('currency_code')->pluck('total','currency_code');

    $incomeByC = \App\Models\IncomeEntry::selectRaw('currency_code, sum(amount) as total')
        ->groupBy('currency_code')->pluck('total','currency_code');

    $expenseByC = \App\Models\Expense::selectRaw('currency_code, sum(amount) as total')
        ->groupBy('currency_code')->pluck('total','currency_code');

    $finCurrencies = collect(array_keys(array_merge(
        $invoicedByC->toArray(), $paidByC->toArray(),
        $incomeByC->toArray(),   $expenseByC->toArray()
    )))->unique()->sort()->values();
@endphp

{{-- رأس الصفحة --}}
<div class="mb-7">
    <h1 class="text-2xl font-bold text-[#3D3D3D]">لوحة التحكم</h1>
    <p class="text-sm text-gray-400 mt-0.5">{{ now()->locale('ar')->isoFormat('dddd، D MMMM YYYY') }}</p>
</div>

{{-- بطاقات الإحصاء --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <a href="{{ route('clients.index') }}" class="card p-5 hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 group-hover:bg-blue-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 group-hover:text-[#C9A227] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </div>
        <div class="text-3xl font-bold text-[#3D3D3D]">{{ number_format($clientCount) }}</div>
        <div class="text-sm text-gray-400 mt-0.5">إجمالي العملاء</div>
    </a>

    <a href="{{ route('invoices.index') }}" class="card p-5 hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500 group-hover:bg-amber-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <span class="badge badge-yellow">{{ $invoiceOpen }} مفتوحة</span>
        </div>
        <div class="text-3xl font-bold text-[#C9A227]">{{ number_format($invoiceTotal) }}</div>
        <div class="text-sm text-gray-400 mt-0.5">إجمالي الفواتير</div>
    </a>

    <a href="{{ route('suppliers.index') }}" class="card p-5 hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-500 group-hover:bg-purple-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 group-hover:text-[#C9A227] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </div>
        <div class="text-3xl font-bold text-[#3D3D3D]">{{ number_format($supplierCount) }}</div>
        <div class="text-sm text-gray-400 mt-0.5">إجمالي الموردين</div>
    </a>

    <a href="{{ route('payments.index') }}" class="card p-5 hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-500 group-hover:bg-green-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 group-hover:text-[#C9A227] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </div>
        <div class="text-3xl font-bold text-[#3D3D3D]">{{ number_format($paymentCount) }}</div>
        <div class="text-sm text-gray-400 mt-0.5">دفعات العملاء</div>
    </a>

</div>

{{-- ═══ الملخص المالي (مخفي افتراضياً) ═══ --}}
<div x-data="{ open: false }" class="mb-8">

    {{-- شريط الإظهار --}}
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-widest">الملخص المالي</h2>
        <button @click="open = !open"
                class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border border-[#E2E4E9] bg-white text-gray-500 hover:border-[#C9A227] hover:text-[#C9A227] transition select-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      x-show="!open" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      x-show="open"  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
            <span x-text="open ? 'إخفاء الأرقام' : 'إظهار الأرقام'"></span>
        </button>
    </div>

    {{-- المحتوى --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         x-cloak>

        @if($finCurrencies->isEmpty())
        <div class="card p-6 text-center text-sm text-gray-300">لا توجد بيانات مالية مسجّلة بعد</div>
        @else
        <div class="grid grid-cols-1 lg:grid-cols-{{ min($finCurrencies->count(), 3) }} gap-4">
            @foreach($finCurrencies as $cur)
            @php
                $invoiced = (float)($invoicedByC[$cur] ?? 0);
                $paid     = (float)($paidByC[$cur]     ?? 0);
                $income   = (float)($incomeByC[$cur]   ?? 0);
                $expense  = (float)($expenseByC[$cur]  ?? 0);
                $balance  = $invoiced - $paid;
                $net      = $income - $expense;
                $pct      = $invoiced > 0 ? min(100, round($paid / $invoiced * 100)) : 0;
            @endphp
            <div class="card p-5">

                {{-- العنوان --}}
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest" dir="ltr">{{ $cur }}</span>
                    <span class="badge {{ $balance <= 0 ? 'badge-green' : 'badge-yellow' }}">
                        {{ $balance <= 0 ? 'محصّل بالكامل' : 'رصيد مستحق' }}
                    </span>
                </div>

                {{-- الأرقام الأربعة --}}
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-blue-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-blue-400 mb-1">الفواتير الصادرة</p>
                        <p class="text-lg font-bold text-blue-700 leading-none" dir="ltr">{{ number_format($invoiced, 2) }}</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-green-400 mb-1">الدفعات المستلمة</p>
                        <p class="text-lg font-bold text-green-700 leading-none" dir="ltr">{{ number_format($paid, 2) }}</p>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-amber-400 mb-1">الإيرادات المسجّلة</p>
                        <p class="text-lg font-bold text-amber-700 leading-none" dir="ltr">{{ number_format($income, 2) }}</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-red-400 mb-1">المصروفات</p>
                        <p class="text-lg font-bold text-red-700 leading-none" dir="ltr">{{ number_format($expense, 2) }}</p>
                    </div>
                </div>

                {{-- شريط تحصيل الفواتير --}}
                @if($invoiced > 0)
                <div class="mb-3">
                    <div class="flex justify-between text-[10px] text-gray-400 mb-1">
                        <span>نسبة التحصيل</span>
                        <span dir="ltr">{{ $pct }}%</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $pct >= 100 ? 'bg-green-500' : ($pct >= 50 ? 'bg-[#C9A227]' : 'bg-red-400') }} transition-all"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endif

                {{-- الرصيد المستحق / صافي الإيرادات --}}
                <div class="flex items-center justify-between pt-3 border-t border-[#E2E4E9]">
                    <div>
                        <p class="text-[10px] text-gray-400">رصيد مستحق من العملاء</p>
                        <p class="text-base font-bold {{ $balance > 0 ? 'text-red-500' : 'text-green-600' }}" dir="ltr">
                            {{ number_format(abs($balance), 2) }}
                            @if($balance < 0)<span class="text-xs font-normal text-green-500"> زيادة</span>@endif
                        </p>
                    </div>
                    <div class="text-left">
                        <p class="text-[10px] text-gray-400">صافي الإيرادات</p>
                        <p class="text-base font-bold {{ $net >= 0 ? 'text-[#C9A227]' : 'text-red-500' }}" dir="ltr">
                            {{ $net >= 0 ? '+' : '-' }}{{ number_format(abs($net), 2) }}
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- آخر الفواتير --}}
<div class="card overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-[#E2E4E9]">
        <h2 class="text-base font-semibold text-[#3D3D3D]">آخر الفواتير</h2>
        <a href="{{ route('invoices.index') }}" class="text-xs text-[#C9A227] hover:underline font-medium">عرض الكل</a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>العميل</th>
                <th>التاريخ</th>
                <th>العملة</th>
                <th>المبلغ</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @forelse(\App\Models\Invoice::with('client')->latest('document_date')->limit(8)->get() as $inv)
            <tr>
                <td class="font-medium">{{ $inv->client?->displayName() ?? '—' }}</td>
                <td class="text-gray-500" dir="ltr">{{ $inv->document_date?->format('Y-m-d') }}</td>
                <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $inv->currency_code }}</td>
                <td class="font-mono font-medium text-xs" dir="ltr">{{ number_format((float)$inv->total_amount,2) }}</td>
                <td>
                    @php $s = $inv->status; @endphp
                    <span class="badge {{ $s==='issued' ? 'badge-green' : ($s==='draft' ? 'badge-yellow' : 'badge-red') }}">
                        {{ $s==='issued' ? 'صادرة' : ($s==='draft' ? 'مسودة' : 'ملغاة') }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-10 text-gray-300 text-sm">لا توجد فواتير بعد</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

</x-layouts.app>
