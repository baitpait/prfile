@php
    $invoicedByC = \App\Models\Invoice::where('status', 'issued')
        ->selectRaw('currency_code, sum(total_amount) as total')
        ->groupBy('currency_code')->pluck('total', 'currency_code');

    $paidByC = \App\Models\ClientPayment::selectRaw('currency_code, sum(amount) as total')
        ->groupBy('currency_code')->pluck('total', 'currency_code');

    $expenseByC = \App\Models\Expense::selectRaw('currency_code, sum(amount) as total')
        ->groupBy('currency_code')->pluck('total', 'currency_code');

    $poSupByC = \App\Models\PurchaseOrder::where('status', 'issued')->whereNull('deleted_at')
        ->selectRaw('currency_code, sum(total_amount) as total')
        ->groupBy('currency_code')->pluck('total', 'currency_code');

    $paidSupByC = \App\Models\SupplierPayment::whereNull('deleted_at')
        ->selectRaw('currency_code, sum(amount) as total')
        ->groupBy('currency_code')->pluck('total', 'currency_code');

    $finCurrencies = collect(array_keys(array_merge(
        $invoicedByC->toArray(),
        $paidByC->toArray(),
        $expenseByC->toArray(),
        $poSupByC->toArray(),
        $paidSupByC->toArray(),
    )))->unique()->sort()->values();
@endphp

@if($finCurrencies->isEmpty())
    <div class="card p-6 text-center text-sm text-gray-300">لا توجد بيانات مالية مسجّلة بعد</div>
@else
    <div class="grid grid-cols-1 lg:grid-cols-{{ min($finCurrencies->count(), 3) }} gap-4">
        @foreach($finCurrencies as $cur)
            @php
                $invoiced = (float) ($invoicedByC[$cur] ?? 0);
                $paid = (float) ($paidByC[$cur] ?? 0);
                $expense = (float) ($expenseByC[$cur] ?? 0);
                $balance = $invoiced - $paid;
                $net = $paid - $expense;
                $pct = $invoiced > 0 ? min(100, round($paid / $invoiced * 100)) : 0;
                $supplierOwed = (float) ($poSupByC[$cur] ?? 0) - (float) ($paidSupByC[$cur] ?? 0);
            @endphp
            <div class="card p-5">

                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest" dir="ltr">{{ $cur }}</span>
                    <span class="badge {{ $balance <= 0 ? 'badge-green' : 'badge-yellow' }}">
                        {{ $balance <= 0 ? 'محصّل بالكامل' : 'رصيد مستحق' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                    <div class="bg-blue-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-blue-400 mb-1">الفواتير الصادرة</p>
                        <p class="text-lg font-bold text-blue-700 leading-none" dir="ltr">{{ number_format($invoiced, 2) }}</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-green-400 mb-1">دفعات العملاء</p>
                        <p class="text-lg font-bold text-green-700 leading-none" dir="ltr">{{ number_format($paid, 2) }}</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-red-400 mb-1">المصروفات</p>
                        <p class="text-lg font-bold text-red-700 leading-none" dir="ltr">{{ number_format($expense, 2) }}</p>
                    </div>
                </div>

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

                <div class="flex items-center justify-between pt-3 border-t border-[#E2E4E9]">
                    <div>
                        <p class="text-[10px] text-gray-400">رصيد مستحق من العملاء</p>
                        <p class="text-base font-bold {{ $balance > 0 ? 'text-red-500' : 'text-green-600' }}" dir="ltr">
                            {{ number_format(abs($balance), 2) }}
                            @if($balance < 0)<span class="text-xs font-normal text-green-500"> زيادة</span>@endif
                        </p>
                    </div>
                    <div class="text-left">
                        <p class="text-[10px] text-gray-400">صافي (دفعات العملاء − مصروفات)</p>
                        <p class="text-base font-bold {{ $net >= 0 ? 'text-[#C9A227]' : 'text-red-500' }}" dir="ltr">
                            {{ $net >= 0 ? '+' : '-' }}{{ number_format(abs($net), 2) }}
                        </p>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-dashed border-[#E2E4E9]">
                    <p class="text-[10px] text-gray-400 mb-0.5">التزام تجاه الموردين (أوامر شراء − دفعات)</p>
                    <p class="text-sm font-bold {{ $supplierOwed > 0 ? 'text-purple-600' : 'text-green-600' }}" dir="ltr">
                        {{ number_format($supplierOwed, 2) }} {{ $cur }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>
@endif
