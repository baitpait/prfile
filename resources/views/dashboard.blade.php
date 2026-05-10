<x-layouts.app title="لوحة التحكم">
    <h1 class="text-2xl font-bold text-[#3D3D3D] mb-6">لوحة التحكم</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border border-[#E0E0E0] rounded p-5">
            <div class="text-sm text-gray-500 mb-1">إجمالي العملاء</div>
            <div class="text-3xl font-bold text-[#3D3D3D]">
                {{ \App\Models\Client::count() }}
            </div>
        </div>
        <div class="bg-white border border-[#E0E0E0] rounded p-5">
            <div class="text-sm text-gray-500 mb-1">فواتير مفتوحة</div>
            <div class="text-3xl font-bold text-[#C9A227]">
                {{ \App\Models\Invoice::where('status', 'issued')->count() }}
            </div>
        </div>
        <div class="bg-white border border-[#E0E0E0] rounded p-5">
            <div class="text-sm text-gray-500 mb-1">الموردون</div>
            <div class="text-3xl font-bold text-[#3D3D3D]">
                {{ \App\Models\Supplier::count() }}
            </div>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-lg font-semibold mb-3">آخر الفواتير</h2>
        <div class="bg-white border border-[#E0E0E0] rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#F5F5F5]">
                    <tr>
                        <th class="text-right px-4 py-3 font-semibold">العميل</th>
                        <th class="text-right px-4 py-3 font-semibold">التاريخ</th>
                        <th class="text-right px-4 py-3 font-semibold">العملة</th>
                        <th class="text-right px-4 py-3 font-semibold">المبلغ</th>
                        <th class="text-right px-4 py-3 font-semibold">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\Invoice::with('client')->latest('document_date')->limit(10)->get() as $invoice)
                    <tr class="border-t border-[#E0E0E0] hover:bg-[#F5F5F5]">
                        <td class="px-4 py-2">{{ $invoice->client->displayName() }}</td>
                        <td class="px-4 py-2" dir="ltr">{{ $invoice->document_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-2" dir="ltr">{{ $invoice->currency_code }}</td>
                        <td class="px-4 py-2 font-mono" dir="ltr">{{ number_format((float)$invoice->total_amount, 2) }}</td>
                        <td class="px-4 py-2">
                            @if($invoice->status === 'issued')
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">صادرة</span>
                            @elseif($invoice->status === 'draft')
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">مسودة</span>
                            @else
                                <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded">ملغاة</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if(\App\Models\Invoice::count() === 0)
            <div class="text-center py-8 text-gray-400 text-sm">لا توجد فواتير بعد.</div>
            @endif
        </div>
    </div>
</x-layouts.app>
