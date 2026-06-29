<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@include('pdf.reports.partials.styles')
</head>
<body>

@include('pdf.reports.partials.header')

@if($rows->isEmpty())
<p class="empty">لا توجد حركات في هذه الفترة.</p>
@else
@foreach($rows as $cur => $row)
<div class="summary-box" style="margin-bottom:12px;">
    <div class="summary-row"><span><strong dir="ltr">{{ $cur }}</strong></span><span></span></div>
    <div class="summary-row"><span>{{ $salesLabel }}</span><span class="positive" dir="ltr">{{ number_format($row['sales'], 2) }}</span></div>
    <div class="summary-row"><span>{{ $purchaseLabel }}</span><span class="negative" dir="ltr">{{ number_format($row['purchases'], 2) }}</span></div>
    <div class="summary-row"><span>مصروفات</span><span class="negative" dir="ltr">{{ number_format($row['expenses'], 2) }}</span></div>
    <div class="summary-row"><span>رواتب</span><span class="negative" dir="ltr">{{ number_format($row['salaries'], 2) }}</span></div>
    <div class="summary-row"><span><strong>صافي الربح/الخسارة</strong></span>
        <span class="{{ $row['net_profit'] >= 0 ? 'positive' : 'negative' }}" dir="ltr"><strong>{{ number_format($row['net_profit'], 2) }} {{ $cur }}</strong></span>
    </div>
</div>
@endforeach
@endif

</body>
</html>
