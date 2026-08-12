<div>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">صندوق الشيكات</h1>
        <p class="text-sm text-gray-400 mt-0.5">سجل الشيكات المستلمة — حركة، عملة، استحقاق، وتفاصيل التظهير</p>
    </div>
    <a href="{{ route('received-checks.due-report') }}" wire:navigate class="btn btn-secondary text-sm" style="text-decoration:none;">
        تقرير الاستحقاق
    </a>
</div>

@if(!empty($pendingSummary))
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    @foreach($pendingSummary as $cur => $box)
    <button type="button" wire:click="$set('currency', '{{ $cur }}')"
            class="card p-4 text-right hover:border-[#C9A227] transition-colors {{ $currency === $cur ? 'ring-2 ring-[#C9A227]' : '' }}">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1" dir="ltr">{{ $cur }}</p>
        <p class="text-xs text-gray-500 mb-1">قيد المعالجة</p>
        <p class="text-lg font-bold text-[#C9A227]" dir="ltr">{{ number_format($box['total'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $box['count'] }} شيك</p>
    </button>
    @endforeach
</div>
@endif

@if(($dueCounts['overdue'] ?? 0) > 0 || ($dueCounts['due_soon'] ?? 0) > 0)
<div class="flex flex-wrap gap-2 mb-4 text-xs">
    @if(($dueCounts['overdue'] ?? 0) > 0)
    <span class="badge badge-red">متأخر: {{ $dueCounts['overdue'] }}</span>
    @endif
    @if(($dueCounts['due_soon'] ?? 0) > 0)
    <span class="badge badge-yellow">يستحق خلال 7 أيام: {{ $dueCounts['due_soon'] }}</span>
    @endif
</div>
@endif

<div class="card p-4 mb-5 flex flex-wrap gap-4 items-end">
    <div>
        <label class="label">الحالة</label>
        <select wire:model.live="status" class="input select min-w-[160px]">
            <option value="">— الكل —</option>
            @foreach($statusOptions as $code)
            <option value="{{ $code }}">{{ \App\Services\Finance\ReceivedCheckStatus::label($code) }}</option>
            @endforeach
        </select>
    </div>
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
        <label class="label">الاستحقاق</label>
        <select wire:model.live="due" class="input select min-w-[160px]">
            <option value="">— الكل —</option>
            <option value="overdue">متأخر (pending)</option>
            <option value="due_soon">خلال 7 أيام (pending)</option>
        </select>
    </div>
    @if($currency !== '' || $status !== '' || $due !== '')
    <button type="button" wire:click="$set('currency', ''); $set('status', ''); $set('due', '')"
            class="btn btn-secondary text-xs">مسح الفلاتر</button>
    @endif
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>العميل</th>
                    <th>البنك</th>
                    <th>صاحب الشيك</th>
                    <th dir="ltr">رقم الشيك</th>
                    <th dir="ltr">الاستحقاق</th>
                    <th dir="ltr">المبلغ</th>
                    <th>الحالة</th>
                    <th>التظهير</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                @php
                    $isOverdue = $row->isPending() && $row->due_date && $row->due_date->lt(now()->startOfDay());
                @endphp
                <tr wire:key="check-{{ $row->id }}" class="{{ $isOverdue ? 'bg-red-50/40' : '' }}">
                    <td class="font-medium">{{ $row->client?->displayName() ?? '—' }}</td>
                    <td>{{ $row->bank_name }}</td>
                    <td>{{ $row->drawer_name }}</td>
                    <td dir="ltr" class="font-mono">{{ $row->check_number }}</td>
                    <td dir="ltr" class="{{ $isOverdue ? 'text-red-600 font-semibold' : '' }}">
                        {{ $row->due_date?->format('Y-m-d') }}
                    </td>
                    <td dir="ltr" class="font-mono font-semibold">
                        {{ number_format((float) $row->amount, 2) }} {{ $row->currency_code }}
                    </td>
                    <td>
                        <span class="badge {{ \App\Services\Finance\ReceivedCheckStatus::badgeClass($row->status) }}">
                            {{ $row->statusLabel() }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-600">
                        @if($row->isEndorsed())
                            {{ $row->endorsementTargetLabel() ?? '—' }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1 justify-end flex-wrap">
                            <a href="{{ route('received-checks.show', $row) }}" wire:navigate
                               class="btn btn-ghost py-1 px-2 text-xs">عرض</a>
                            @if($row->isPending() && auth()->user()->isAccountant())
                            <button type="button" wire:click="markCleared({{ $row->id }})"
                                    class="btn btn-ghost py-1 px-2 text-xs text-green-700">صُرف</button>
                            <button type="button" wire:click="markNotCleared({{ $row->id }})"
                                    class="btn btn-ghost py-1 px-2 text-xs text-red-600">لم يُصرف</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-12 text-gray-400">لا توجد شيكات مسجّلة.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-[#E2E4E9]">
        <x-list-pagination :paginator="$rows" />
    </div>
</div>
</div>
