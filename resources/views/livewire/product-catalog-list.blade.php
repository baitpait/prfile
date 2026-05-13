<div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">كتالوج الخدمات</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} عنصر في الأرشيف</p>
    </div>
</div>

<p class="text-sm text-gray-500 mb-4 leading-relaxed">
    قائمة بأسماء وأسعار من <strong>النظام القديم</strong> (ملف Products.xml عند الاستيراد). بنود الفواتير الحالية <strong>نص حر</strong> ولا ترتبط آلياً بهذا الجدول.
</p>

<div class="card px-4 py-3 mb-5 flex flex-wrap items-center gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
    <input wire:model.live.debounce.300ms="search" type="search" placeholder="بحث بالاسم أو الكود أو الوصف..."
           class="flex-1 min-w-[12rem] bg-transparent text-sm focus:outline-none placeholder:text-gray-300">
    @if($search)<button wire:click="$set('search','')" class="text-gray-300 hover:text-gray-500 text-lg leading-none">&times;</button>@endif
</div>

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#C9A227]/20 relative overflow-hidden"><div class="absolute inset-y-0 right-0 w-1/3 bg-[#C9A227] animate-pulse"></div></div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="w-16">#</th>
                <th>الاسم</th>
                <th>الكود</th>
                <th class="text-left" dir="ltr">سعر البيع</th>
                <th class="text-left" dir="ltr">سعر الشراء</th>
                <th>التصنيف</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            @php $f = $row->flat(); @endphp
            <tr>
                <td class="text-gray-400 font-mono text-xs">{{ $row->id }}</td>
                <td class="font-semibold">{{ $row->displayName() }}</td>
                <td class="font-mono text-xs" dir="ltr">{{ $row->productCode() }}</td>
                <td class="font-mono text-xs" dir="ltr">{{ $f['UnitPrice'] ?? '—' }}</td>
                <td class="font-mono text-xs" dir="ltr">{{ $f['BuyPrice'] ?? '—' }}</td>
                <td class="text-sm text-gray-500">{{ trim((string) ($f['Category'] ?? '')) !== '' ? $f['Category'] : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6">
                <div class="text-center py-16 text-gray-300">
                    <p class="text-sm">{{ $search ? 'لا توجد نتائج' : 'لا توجد خدمات في الأرشيف' }}</p>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>

    <x-list-pagination :paginator="$rows" />
</div>

</div>
