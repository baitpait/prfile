<x-layouts.app title="تفاصيل السلفة">
@php
    $salaryAdvance->load(['employee', 'settledSalaryPayment', 'receivedCheck']);
@endphp
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('salary-advances.index') }}" wire:navigate
           style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#fff;border:1px solid #E2E4E9;color:#9CA3AF;text-decoration:none;"
           onmouseover="this.style.color='#3D3D3D'" onmouseout="this.style.color='#9CA3AF'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#3D3D3D]">تفاصيل السلفة</h1>
            <p class="text-sm text-gray-400 mt-0.5">{{ $salaryAdvance->employee?->displayName() }}</p>
        </div>
    </div>

    <div class="card p-6">
        <p class="text-2xl font-bold text-[#C9A227] mb-6" dir="ltr">
            {{ number_format((float)$salaryAdvance->amount, 2) }}
            <span class="text-sm font-normal text-gray-400">{{ $salaryAdvance->currency_code }}</span>
        </p>

        <dl class="divide-y divide-[#E2E4E9] text-sm">
            <div class="flex justify-between py-3"><dt class="text-gray-500">الحالة</dt><dd>{{ App\Models\SalaryAdvance::statusLabel($salaryAdvance->status) }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-gray-500">تاريخ الدفع</dt><dd dir="ltr">{{ $salaryAdvance->paid_at?->format('Y-m-d') }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-gray-500">طريقة الدفع</dt><dd>{{ $salaryAdvance->method ?? '—' }}</dd></div>
            @if($salaryAdvance->bank_reference)
            <div class="flex justify-between py-3"><dt class="text-gray-500">مرجع بنكي</dt><dd dir="ltr">{{ $salaryAdvance->bank_reference }}</dd></div>
            @endif
            @if($salaryAdvance->settledSalaryPayment)
            <div class="flex justify-between py-3">
                <dt class="text-gray-500">راتب التسوية</dt>
                <dd>
                    <a href="{{ route('salary-payments.show', $salaryAdvance->settledSalaryPayment) }}" wire:navigate
                       class="text-[#C9A227] font-semibold" style="text-decoration:none;">
                        {{ $salaryAdvance->settledSalaryPayment->periodLabel() }}
                    </a>
                </dd>
            </div>
            @endif
            @if($salaryAdvance->receivedCheck)
            <div class="flex justify-between py-3">
                <dt class="text-gray-500">شيك مُستلم (تظهير)</dt>
                <dd>
                    <a href="{{ route('received-checks.show', $salaryAdvance->receivedCheck) }}" wire:navigate
                       class="text-[#C9A227] font-semibold" style="text-decoration:none;">
                        {{ $salaryAdvance->receivedCheck->check_number }} — {{ $salaryAdvance->receivedCheck->bank_name }}
                    </a>
                </dd>
            </div>
            @endif
            @if($salaryAdvance->notes)
            <div class="py-3"><dt class="text-gray-500 mb-1">ملاحظات</dt><dd class="bg-amber-50 rounded-lg p-3">{{ $salaryAdvance->notes }}</dd></div>
            @endif
        </dl>

        <div class="flex justify-end gap-2 pt-5 mt-2 border-t border-[#E2E4E9]">
            <a href="{{ route('salary-advances.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">رجوع</a>
            @can('update', $salaryAdvance)
            <a href="{{ route('salary-advances.edit', $salaryAdvance) }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">تعديل</a>
            @endcan
        </div>
    </div>
</div>
</x-layouts.app>
