<div>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">تقرير استحقاق الشيكات</h1>
        <p class="text-sm text-gray-400 mt-0.5">شيكات «قيد المعالجة» فقط — حسب تاريخ الاستحقاق</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @can('exportDueReport', App\Models\ReceivedCheck::class)
        <a href="{{ $pdfExportUrl }}" target="_blank" rel="noopener"
           class="px-4 py-2 text-sm bg-[#3D3D3D] text-white rounded hover:bg-[#2a2a2a] font-medium"
           style="text-decoration:none;">
            تصدير PDF
        </a>
        <button type="button" wire:click="exportCsv"
                class="px-4 py-2 text-sm bg-white border border-[#E0E0E0] rounded hover:bg-[#F5F5F5] font-medium">
            تصدير CSV
        </button>
        @endcan
        <a href="{{ route('received-checks.index') }}" wire:navigate class="btn btn-secondary text-sm" style="text-decoration:none;">← صندوق الشيكات</a>
    </div>
</div>

@if(!empty($totalsByCurrency))
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    @foreach($totalsByCurrency as $cur => $box)
    <div class="card p-4">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1" dir="ltr">{{ $cur }}</p>
        <p class="text-lg font-bold text-[#C9A227]" dir="ltr">{{ number_format($box['total'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $box['count'] }} شيك</p>
    </div>
    @endforeach
</div>
@endif

<div class="flex flex-wrap gap-2 mb-4 text-xs">
    @foreach($bucketOptions as $code => $label)
    @php $cnt = $countsByBucket[$code] ?? 0; @endphp
    @if($cnt > 0)
    <button type="button" wire:click="$set('bucket', '{{ $bucket === $code ? '' : $code }}')"
            class="badge {{ $bucket === $code ? 'badge-yellow ring-2 ring-[#C9A227]' : 'badge-gray' }}">
        {{ $label }}: {{ $cnt }}
    </button>
    @endif
    @endforeach
</div>

<div class="card p-4 mb-5 flex flex-wrap gap-4 items-end">
    <div>
        <label class="label">العملة</label>
        <select wire:model.live="currency" class="input select min-w-[120px]">
            <option value="">— الكل —</option>
            @foreach($currencyOptions as $cur)
            <option value="{{ $cur }}" dir="ltr">{{ $cur }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">فترة الاستحقاق</label>
        <select wire:model.live="bucket" class="input select min-w-[180px]">
            <option value="">— الكل —</option>
            @foreach($bucketOptions as $code => $label)
            <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>العميل</th>
                <th>رقم الشيك</th>
                <th>الاستحقاق</th>
                <th>أيام</th>
                <th>الفترة</th>
                <th class="text-left" dir="ltr">المبلغ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            @php
                $days = $service->daysUntilDue($row);
                $bucketCode = $service->bucketForCheck($row);
            @endphp
            <tr>
                <td>{{ $row->client?->displayName() ?? '—' }}</td>
                <td class="font-mono text-xs" dir="ltr">{{ $row->check_number }}</td>
                <td dir="ltr">{{ $row->due_date?->format('Y-m-d') ?? '—' }}</td>
                <td dir="ltr" class="{{ $days < 0 ? 'text-red-600 font-semibold' : '' }}">{{ $days }}</td>
                <td>{{ $bucketOptions[$bucketCode] ?? '—' }}</td>
                <td class="text-left font-mono" dir="ltr">{{ number_format((float) $row->amount, 2) }} {{ $row->currency_code }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-gray-400 py-8">لا توجد شيكات pending مطابقة للفلاتر.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>
