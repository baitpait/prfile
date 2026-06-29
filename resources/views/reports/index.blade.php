<x-layouts.app title="مركز التقارير">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-[#3D3D3D]">مركز التقارير</h1>
        <p class="text-sm text-gray-500 mt-1">تقارير مالية حسب الفترة — معاينة على الشاشة وتصدير PDF و CSV.</p>
    </div>

    @php
    $cards = [
        ['route' => 'reports.financial-period', 'title' => 'لوحة الفترة المالية', 'desc' => 'ملخص مبيعات ومشتريات وصافي نقدي لكل عملة', 'color' => 'border-[#C9A227]'],
        ['route' => 'reports.activity-log', 'title' => 'سجل النشاط المالي', 'desc' => 'كل الحركات المالية في جدول زمني واحد', 'color' => 'border-gray-500'],
        ['route' => 'reports.cashflow', 'title' => 'كشف التدفق النقدي', 'desc' => 'دفعات العملاء والموردين والمصروفات في فترة واحدة', 'color' => 'border-teal-400'],
        ['route' => 'reports.client-payments', 'title' => 'دفعات العملاء', 'desc' => 'تفصيل كل دفعة عميل بين تاريخين', 'color' => 'border-green-400'],
        ['route' => 'reports.supplier-payments', 'title' => 'دفعات الموردين', 'desc' => 'تفصيل كل دفعة مورد بين تاريخين', 'color' => 'border-purple-400'],
        ['route' => 'reports.expenses', 'title' => 'المصروفات', 'desc' => 'تفصيل المصروفات بين تاريخين', 'color' => 'border-red-400'],
        ['route' => 'reports.sales', 'title' => 'تقرير المبيعات', 'desc' => 'فواتير صادرة مع حالة الدفع', 'color' => 'border-blue-400'],
        ['route' => 'reports.purchase-orders', 'title' => 'تقرير المشتريات', 'desc' => 'أوامر شراء صادرة مع حالة الدفع', 'color' => 'border-indigo-400'],
        ['route' => 'reports.client-receivables-aging', 'title' => 'أعمار ذمم العملاء', 'desc' => 'رصيد مستحق لكل عميل حسب أيام التأخير', 'color' => 'border-slate-400'],
        ['route' => 'reports.client-receivables-summary', 'title' => 'ملخص ذمم العملاء', 'desc' => 'رصيد مستحق لكل عميل حتى تاريخ — بدون عمرية', 'color' => 'border-sky-400'],
        ['route' => 'reports.aggregated-client-statements', 'title' => 'كشوف العملاء المجمّعة', 'desc' => 'ملخص كشف حساب لكل عميل ضمن الفترة', 'color' => 'border-cyan-400'],
        ['route' => 'reports.supplier-receivables-aging', 'title' => 'أعمار ذمم الموردين', 'desc' => 'متبقٍ لكل مورد حسب أيام التأخير', 'color' => 'border-violet-400'],
        ['route' => 'reports.supplier-payables-summary', 'title' => 'ملخص ذمم الموردين', 'desc' => 'متبقٍ لكل مورد حتى تاريخ — بدون عمرية', 'color' => 'border-indigo-400'],
        ['route' => 'reports.aggregated-supplier-statements', 'title' => 'كشوف الموردين المجمّعة', 'desc' => 'ملخص كشف حساب لكل مورد ضمن الفترة', 'color' => 'border-blue-400'],
        ['route' => 'reports.supplier-adjustments', 'title' => 'تسويات الموردين', 'desc' => 'تسويات ذمة الموردين في فترة', 'color' => 'border-fuchsia-400'],
        ['route' => 'reports.client-adjustments', 'title' => 'تسويات العملاء', 'desc' => 'تسويات ذمة العملاء في فترة', 'color' => 'border-pink-400'],
    ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($cards as $card)
        <a href="{{ route($card['route']) }}" wire:navigate
           class="card p-5 border-r-4 {{ $card['color'] }} hover:shadow-md transition block"
           style="text-decoration:none;">
            <h2 class="text-lg font-bold text-[#3D3D3D] mb-1">{{ $card['title'] }}</h2>
            <p class="text-sm text-gray-500">{{ $card['desc'] }}</p>
        </a>
        @endforeach
    </div>

    <div class="mt-8 card p-4 text-sm text-gray-500">
        <p class="font-semibold text-[#3D3D3D] mb-2">كشوف حساب فردية</p>
        <p>كشف حساب عميل أو مورد (مع PDF) متاح من صفحة العميل/المورد — وليس من هنا.</p>
    </div>
</x-layouts.app>
