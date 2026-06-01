<div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">فواتير المشتريات</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} مستند مسجّل</p>
    </div>
    @can('create', \App\Models\PurchaseOrder::class)
    <a href="{{ route('purchase-orders.create') }}" wire:navigate class="btn btn-primary" style="font-size:13px;text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        فاتورة مشتريات جديدة
    </a>
    @endcan
</div>

<div class="card px-4 py-3 mb-5 flex items-center gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
    <input wire:model.live.debounce.300ms="search" type="search"
           placeholder="بحث برقم المستند، المورد، الهاتف، أو الملاحظات..."
           class="flex-1 bg-transparent text-sm focus:outline-none placeholder:text-gray-300"
           autocomplete="off">
    @if($search !== '')
    <button type="button" wire:click="$set('search','')" class="text-gray-300 hover:text-gray-500 text-lg leading-none" aria-label="مسح البحث">&times;</button>
    @endif
</div>

<div class="card p-4 mb-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
        <div class="flex min-w-0 flex-1 flex-col gap-3">
            <div class="min-w-0 w-full">
                <label class="label">بحث المورد</label>
                <input type="search" wire:model.live.debounce.300ms="supplierSearch" class="input w-full text-sm" placeholder="ابحث باسم المورد..." autocomplete="off">
            </div>

            <div class="grid min-w-0 w-full grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">الحالة</label>
                    <select wire:model.live="filterStatus" class="input w-full">
                        <option value="">الكل</option>
                        <option value="draft">مسودة</option>
                        <option value="issued">صادر</option>
                        <option value="void">ملغى</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label class="label">المورد</label>
                    <select wire:model.live="filterSupplierId" class="input w-full">
                        <option value="">كل الموردين</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if(count($poCurrencies) > 0)
            <div class="min-w-0 w-full sm:max-w-xs">
                <label class="label">العملة</label>
                <select wire:model.live="filterCurrency" class="input w-full">
                    <option value="">كل العملات</option>
                    @foreach($poCurrencies as $code)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">من تاريخ المستند</label>
                    <input wire:model.live="filterDateFrom" type="date" class="input w-full" dir="ltr">
                </div>
                <div class="min-w-0">
                    <label class="label">إلى تاريخ المستند</label>
                    <input wire:model.live="filterDateTo" type="date" class="input w-full" dir="ltr">
                </div>
            </div>
        </div>
        @if($this->hasActivePurchaseOrderFilters())
        <button type="button" wire:click="clearPurchaseOrderFilters" class="btn btn-secondary shrink-0 self-start whitespace-nowrap lg:self-end">
            مسح الفلاتر
        </button>
        @endif
    </div>
</div>

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#C9A227]/20 relative overflow-hidden"><div class="absolute inset-y-0 right-0 w-1/3 bg-[#C9A227] animate-pulse"></div></div>
    <table class="data-table">
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>المورد</th>
                <th>رقم المستند</th>
                <th>الحالة</th>
                <th class="text-left" dir="ltr">المبلغ</th>
                <th class="w-36"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $po)
            <tr>
                <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $po->document_date?->format('Y-m-d') ?? '—' }}</td>
                <td class="font-semibold">{{ $po->supplier?->displayName() ?? '—' }}</td>
                <td class="font-mono text-xs" dir="ltr">{{ $po->legacy_po_no ?? '#'.$po->id }}</td>
                <td>
                    @php $st = $po->status; @endphp
                    <span class="badge {{ $st==='issued' ? 'badge-green' : ($st==='draft' ? 'badge-yellow' : 'badge-red') }}">
                        {{ $st==='issued' ? 'صادر' : ($st==='draft' ? 'مسودة' : 'ملغى') }}
                    </span>
                </td>
                <td class="font-mono font-semibold text-xs" dir="ltr">
                    {{ number_format((float) $po->total_amount, 2) }}
                    <span class="text-gray-400 font-normal">{{ $po->currency_code }}</span>
                </td>
                <td>
                    <div class="flex items-center gap-1 justify-end flex-wrap">
                        <button type="button" wire:click="openView({{ $po->id }})" class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50">عرض</button>
                        @can('update', $po)
                        <a href="{{ route('purchase-orders.edit', $po) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                        @endcan
                        @can('delete', $po)
                        <button type="button" wire:click="confirmDelete({{ $po->id }})" class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">حذف</button>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6">
                <div class="text-center py-16 text-gray-300">
                    <p class="text-sm">{{ $search || $this->hasActivePurchaseOrderFilters() ? 'لا توجد نتائج للبحث أو الفلتر' : 'لا توجد فواتير مشتريات بعد — أضف مستنداً أو استورد من XML' }}</p>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>

    <x-list-pagination :paginator="$rows" />
</div>

{{-- نافذة العرض السريع --}}
@if($viewingId !== null)
<div wire:key="view-{{ $viewingId }}"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="closeView"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-lg mx-0 sm:mx-4 z-10 max-h-[90vh] overflow-y-auto">
        @if($viewingRecord)
        @php $po = $viewingRecord; $st = $po->status; @endphp
        <div class="p-6">
            <div class="flex items-start justify-between mb-5">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-lg font-bold text-[#3D3D3D]">{{ $po->legacy_po_no ?? 'مستند #'.$po->id }}</h2>
                        <span class="badge {{ $st==='issued' ? 'badge-green' : ($st==='draft' ? 'badge-yellow' : 'badge-red') }}">
                            {{ $st==='issued' ? 'صادر' : ($st==='draft' ? 'مسودة' : 'ملغى') }}
                        </span>
                    </div>
                    <p class="text-sm text-[#C9A227] font-semibold">{{ $po->supplier?->displayName() ?? '—' }}</p>
                </div>
                <button type="button" wire:click="closeView" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <dl class="divide-y divide-[#E2E4E9]">
                <div class="flex justify-between py-3">
                    <dt class="text-sm text-gray-500">تاريخ المستند</dt>
                    <dd class="text-sm font-medium text-[#3D3D3D]" dir="ltr">{{ $po->document_date?->format('Y-m-d') ?? '—' }}</dd>
                </div>
                @if($po->due_date)
                <div class="flex justify-between py-3">
                    <dt class="text-sm text-gray-500">تاريخ الاستحقاق</dt>
                    <dd class="text-sm font-medium {{ $po->due_date->isPast() && $st === 'issued' ? 'text-red-500' : 'text-[#3D3D3D]' }}" dir="ltr">{{ $po->due_date->format('Y-m-d') }}</dd>
                </div>
                @endif
                <div class="flex justify-between py-3">
                    <dt class="text-sm text-gray-500">المورد</dt>
                    <dd class="text-sm font-medium text-[#3D3D3D]">{{ $po->supplier?->displayName() ?? '—' }}</dd>
                </div>
                @if($po->notes)
                <div class="py-3">
                    <dt class="text-sm text-gray-500 mb-1">ملاحظات</dt>
                    <dd class="text-sm text-[#3D3D3D] bg-amber-50 rounded-lg p-2">{{ $po->notes }}</dd>
                </div>
                @endif
            </dl>

            @if($po->lines->isNotEmpty())
            <div class="mt-4 mb-2">
                <p class="text-xs font-semibold text-[#C9A227] mb-2 uppercase tracking-wide">البنود</p>
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#3D3D3D] text-white">
                            <th class="text-right px-3 py-2 rounded-r-md">البند</th>
                            <th class="text-center px-2 py-2">الكمية</th>
                            <th class="text-left px-3 py-2 rounded-l-md" dir="ltr">المجموع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($po->lines as $line)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                            <td class="px-3 py-2 border-b border-[#E2E4E9]">
                                <div class="font-semibold text-[#3D3D3D]">{{ $line->title }}</div>
                                @if($line->description)<div class="text-gray-400 text-[10px] mt-0.5">{{ $line->description }}</div>@endif
                            </td>
                            <td class="px-2 py-2 text-center border-b border-[#E2E4E9] text-gray-500">{{ rtrim(rtrim(number_format((float) $line->quantity, 2), '0'), '.') }}</td>
                            <td class="px-3 py-2 border-b border-[#E2E4E9] font-mono font-semibold text-[#3D3D3D]" dir="ltr">{{ number_format((float) $line->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div class="mt-3 pt-3 border-t border-[#E2E4E9] space-y-1">
                @if($po->discount_amount && (float) $po->discount_amount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">الخصم</span>
                    <span class="text-orange-500 font-medium" dir="ltr">- {{ number_format((float) $po->discount_amount, 2) }} {{ $po->currency_code }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-sm font-bold text-[#3D3D3D]">الإجمالي</span>
                    <span class="text-base font-bold text-[#3D3D3D]" dir="ltr">
                        {{ number_format((float) $po->total_amount, 2) }}
                        <span class="text-xs font-normal text-gray-400">{{ $po->currency_code }}</span>
                    </span>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-[#E2E4E9] mt-4">
                <button type="button" wire:click="closeView" class="btn btn-secondary text-xs">إغلاق</button>
                <a href="{{ route('purchase-orders.show', $po) }}" wire:navigate class="btn btn-primary text-xs" style="text-decoration:none;">صفحة التفاصيل</a>
                @can('update', $po)
                <a href="{{ route('purchase-orders.edit', $po) }}" wire:navigate class="btn text-xs bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100" style="text-decoration:none;">تعديل</a>
                @endcan
            </div>
        </div>
        @endif
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
        <h3 class="text-base font-bold text-center mb-1">حذف فاتورة المشتريات</h3>
        <p class="text-sm text-gray-400 text-center mb-5">هل أنت متأكد؟ لا يمكن التراجع.</p>
        <div class="flex gap-2">
            <button type="button" wire:click="cancelDelete" class="btn btn-secondary flex-1">إلغاء</button>
            <button type="button" wire:click="delete" class="btn btn-danger flex-1">حذف</button>
        </div>
    </div>
</div>
@endif

</div>
