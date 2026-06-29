<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@include('pdf.reports.partials.styles')
</head>
<body>
@include('pdf.reports.partials.header')
@if(empty($summary))
<p class="empty">لا توجد حركات في هذه الفترة.</p>
@else
@foreach($summary as $cur => $s)
<div class="summary-box">
    <div class="summary-row"><span><strong>{{ $cur }}</strong> — مبيعات ({{ $s['invoice_count'] }})</span><span>{{ number_format($s['sales'], 2) }}</span></div>
    <div class="summary-row"><span>مشتريات ({{ $s['po_count'] }})</span><span>{{ number_format($s['purchases'], 2) }}</span></div>
    <div class="summary-row"><span>دفعات عملاء</span><span class="positive">{{ number_format($s['client_payments'], 2) }}</span></div>
    <div class="summary-row"><span>دفعات موردين</span><span class="negative">{{ number_format($s['supplier_payments'], 2) }}</span></div>
    <div class="summary-row"><span>مصروفات</span><span class="negative">{{ number_format($s['expenses'], 2) }}</span></div>
    <div class="summary-row"><span><strong>صافي نقدي</strong></span><span><strong>{{ number_format($s['net_cash'], 2) }}</strong></span></div>
    <div class="summary-row"><span>ذمم عملاء (حتى تاريخ «إلى»)</span><span>{{ number_format($s['client_receivables'] ?? 0, 2) }}</span></div>
    <div class="summary-row"><span>التزام موردين (حتى تاريخ «إلى»)</span><span>{{ number_format($s['supplier_payables'] ?? 0, 2) }}</span></div>
</div>
@endforeach
@endif
</body>
</html>
