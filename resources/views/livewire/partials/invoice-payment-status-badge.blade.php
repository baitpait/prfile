@php
    /** @var array{status: string, label: string}|null $paymentStatus */
    $paymentStatus = $paymentStatus ?? null;
@endphp
@if($paymentStatus)
    <span class="badge {{ \App\Services\InvoicePaymentAllocationService::badgeClass($paymentStatus['status']) }}">
        {{ $paymentStatus['label'] }}
    </span>
@else
    <span class="text-xs text-gray-400">—</span>
@endif
