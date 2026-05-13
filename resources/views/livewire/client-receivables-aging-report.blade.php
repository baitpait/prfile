<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#3D3D3D]">أعمار ذمم العملاء</h1>
            <p class="text-sm text-gray-500 mt-1">فواتير صادرة لعملاء لديهم رصيد مستحق، مع مدة المستند والتأخير عن الاستحقاق.</p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-[#3D3D3D] mb-1">تصفية بالعملة</label>
                <select wire:model.live="currency"
                        class="border border-[#E0E0E0] rounded px-3 py-2 text-sm min-w-[10rem] focus:outline-none focus:border-[#C9A227]">
                    <option value="">كل العملات</option>
                    @foreach($currencyOptions as $c)
                        <option value="{{ $c }}" dir="ltr">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            @can('export-client-receivables-aging-csv')
            <button type="button" wire:click="exportCsv"
                    class="px-4 py-2 text-sm bg-white border border-[#E0E0E0] rounded hover:bg-[#F5F5F5] font-medium self-end">
                تصدير CSV
            </button>
            @endcan
        </div>
    </div>

    @if(count($rows) === 0)
        <div class="text-center py-16 text-gray-400 bg-white border border-[#E0E0E0] rounded">
            لا توجد ذمم مستحقة مطابقة للفلتر الحالي.
        </div>
    @else
        <div class="overflow-x-auto bg-white border border-[#E0E0E0] rounded">
            <table class="w-full text-sm">
                <thead class="bg-[#F5F5F5] text-[#3D3D3D]">
                    <tr>
                        <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]">العميل</th>
                        <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]" dir="ltr">العملة</th>
                        <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]">الفاتورة</th>
                        <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]" dir="ltr">تاريخ المستند</th>
                        <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]" dir="ltr">الاستحقاق</th>
                        <th class="text-left px-3 py-2 font-semibold border-b border-[#E0E0E0]" dir="ltr">المبلغ</th>
                        <th class="text-center px-3 py-2 font-semibold border-b border-[#E0E0E0]">أيام منذ المستند</th>
                        <th class="text-center px-3 py-2 font-semibold border-b border-[#E0E0E0]">أيام التأخير</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                    <tr class="border-b border-[#E0E0E0] last:border-0 hover:bg-[#FAFAFA]">
                        <td class="px-3 py-2">
                            <a href="{{ route('clients.statement', $r['client_id']) }}" class="text-[#C9A227] font-medium hover:underline">
                                {{ $r['client_name'] }}
                            </a>
                        </td>
                        <td class="px-3 py-2 font-mono" dir="ltr">{{ $r['currency_code'] }}</td>
                        <td class="px-3 py-2">{{ $r['legacy_invoice_no'] ?? '#'.$r['invoice_id'] }}</td>
                        <td class="px-3 py-2 font-mono" dir="ltr">{{ $r['document_date'] }}</td>
                        <td class="px-3 py-2 font-mono text-gray-600" dir="ltr">{{ $r['due_date'] ?? '—' }}</td>
                        <td class="px-3 py-2 font-mono text-right" dir="ltr">{{ number_format((float) $r['total_amount'], 2) }}</td>
                        <td class="px-3 py-2 text-center font-mono">{{ $r['days_since_document'] }}</td>
                        <td class="px-3 py-2 text-center font-mono">
                            @if($r['days_overdue'] === null)
                                <span class="text-gray-400">—</span>
                            @else
                                {{ $r['days_overdue'] }}
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
