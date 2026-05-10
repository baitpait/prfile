<div>
    {{-- رأس الصفحة --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#3D3D3D]">كشف حساب</h1>
            <p class="text-[#C9A227] font-semibold mt-1">{{ $client->displayName() }}</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="exportCsv"
                    class="px-4 py-2 text-sm bg-white border border-[#E0E0E0] rounded hover:bg-[#F5F5F5] font-medium">
                تصدير CSV
            </button>
            <a href="{{ route('clients.statement.pdf', $client) }}"
               target="_blank"
               class="px-4 py-2 text-sm bg-[#C9A227] text-white rounded hover:opacity-90 font-medium">
                طباعة PDF
            </a>
        </div>
    </div>

    {{-- فلاتر التاريخ --}}
    <div class="bg-white border border-[#E0E0E0] rounded p-4 mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-[#3D3D3D] mb-1">من تاريخ</label>
            <input type="date" wire:model.live="dateFrom"
                   class="border border-[#E0E0E0] rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#C9A227]">
        </div>
        <div>
            <label class="block text-xs font-medium text-[#3D3D3D] mb-1">إلى تاريخ</label>
            <input type="date" wire:model.live="dateTo"
                   class="border border-[#E0E0E0] rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#C9A227]">
        </div>
        @if($dateFrom || $dateTo)
            <button wire:click="resetDates"
                    class="text-sm text-[#DC2626] hover:underline">
                مسح الفلتر
            </button>
        @endif
    </div>

    {{-- لا بيانات --}}
    @if(empty($statement))
        <div class="text-center py-16 text-gray-400">
            لا توجد حركات مالية مسجّلة لهذا العميل.
        </div>
    @endif

    {{-- قسم لكل عملة --}}
    @foreach($statement as $currency => $section)
    <div class="mb-8" id="currency-{{ $currency }}">

        {{-- عنوان العملة --}}
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold text-[#3D3D3D]">
                عملة:
                <span dir="ltr" class="text-[#C9A227] font-mono">{{ $currency }}</span>
            </h2>
            <span class="text-sm font-medium {{ $section['balance'] > 0 ? 'text-[#DC2626]' : 'text-[#16A34A]' }}">
                الرصيد:
                <span dir="ltr" class="font-mono font-bold">
                    {{ number_format($section['balance'], 2) }}
                    {{ $currency }}
                </span>
            </span>
        </div>

        {{-- جدول الفواتير --}}
        @if($section['invoices']->count() > 0)
        <div class="mb-4">
            <h3 class="text-sm font-semibold text-[#3D3D3D] mb-2 bg-[#F5F5F5] px-3 py-1.5 rounded-t border border-[#E0E0E0]">
                الفواتير
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-[#E0E0E0] rounded-b">
                    <thead class="bg-[#F5F5F5] text-[#3D3D3D]">
                        <tr>
                            <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]">التاريخ</th>
                            <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]">رقم الفاتورة</th>
                            <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]">الحالة</th>
                            <th class="text-left px-3 py-2 font-semibold border-b border-[#E0E0E0]" dir="ltr">المبلغ ({{ $currency }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($section['invoices'] as $invoice)
                        <tr class="hover:bg-[#F5F5F5] border-b border-[#E0E0E0] last:border-0">
                            <td class="px-3 py-2" dir="ltr">{{ $invoice->document_date->format('Y-m-d') }}</td>
                            <td class="px-3 py-2">{{ $invoice->legacy_invoice_no ?? '#'.$invoice->id }}</td>
                            <td class="px-3 py-2">
                                @if($invoice->status === 'issued')
                                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">صادرة</span>
                                @elseif($invoice->status === 'draft')
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">مسودة</span>
                                @else
                                    <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded">ملغاة</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-mono text-right" dir="ltr">
                                {{ number_format((float)$invoice->total_amount, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-[#F5F5F5] font-semibold">
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-right">مجموع الفواتير</td>
                            <td class="px-3 py-2 font-mono text-right" dir="ltr">
                                {{ number_format($section['total_invoiced'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

        {{-- جدول الدفعات --}}
        @if($section['payments']->count() > 0)
        <div class="mb-4">
            <h3 class="text-sm font-semibold text-[#3D3D3D] mb-2 bg-[#F5F5F5] px-3 py-1.5 rounded-t border border-[#E0E0E0]">
                الدفعات
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-[#E0E0E0] rounded-b">
                    <thead class="bg-[#F5F5F5] text-[#3D3D3D]">
                        <tr>
                            <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]">التاريخ</th>
                            <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]">طريقة الدفع</th>
                            <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]">المرجع البنكي</th>
                            <th class="text-left px-3 py-2 font-semibold border-b border-[#E0E0E0]" dir="ltr">المبلغ ({{ $currency }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($section['payments'] as $payment)
                        <tr class="hover:bg-[#F5F5F5] border-b border-[#E0E0E0] last:border-0">
                            <td class="px-3 py-2" dir="ltr">{{ $payment->paid_at->format('Y-m-d') }}</td>
                            <td class="px-3 py-2">{{ $payment->method ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $payment->bank_reference ?? '—' }}</td>
                            <td class="px-3 py-2 font-mono text-right text-[#16A34A]" dir="ltr">
                                {{ number_format((float)$payment->amount, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-[#F5F5F5] font-semibold">
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-right">مجموع الدفعات</td>
                            <td class="px-3 py-2 font-mono text-right text-[#16A34A]" dir="ltr">
                                {{ number_format($section['total_paid'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

        {{-- ملخص العملة --}}
        <div class="flex justify-end">
            <div class="bg-white border border-[#E0E0E0] rounded p-4 min-w-64">
                <div class="flex justify-between text-sm mb-1">
                    <span>إجمالي الفواتير</span>
                    <span class="font-mono" dir="ltr">{{ number_format($section['total_invoiced'], 2) }} {{ $currency }}</span>
                </div>
                <div class="flex justify-between text-sm mb-2">
                    <span>إجمالي الدفعات</span>
                    <span class="font-mono text-[#16A34A]" dir="ltr">{{ number_format($section['total_paid'], 2) }} {{ $currency }}</span>
                </div>
                <div class="border-t border-[#E0E0E0] pt-2 flex justify-between font-bold">
                    <span>الرصيد المستحق</span>
                    <span class="font-mono {{ $section['balance'] > 0 ? 'text-[#DC2626]' : 'text-[#16A34A]' }}" dir="ltr">
                        {{ number_format($section['balance'], 2) }} {{ $currency }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>
