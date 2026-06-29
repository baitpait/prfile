<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@include('pdf.reports.partials.styles')
</head>
<body>
@include('pdf.reports.partials.header')
@if(!empty($totals))
<div class="summary-box">
    @foreach($totals as $cur => $t)
    <div class="summary-row"><span>{{ $cur }} ({{ $t['suppliers'] }} مورد)</span><span>{{ number_format($t['balance'], 2) }}</span></div>
    @endforeach
</div>
@endif
@if($rows->isEmpty())
<p class="empty">لا توجد حركات.</p>
@else
<table class="main-table">
    <thead><tr><th>المورد</th><th class="ltr">عملة</th><th class="ltr">أوامر شراء</th><th class="ltr">دفعات</th><th class="ltr">تسويات</th><th class="ltr">المتبقي</th><th class="ltr">حركات</th></tr></thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td>{{ $row['supplier_name'] }}</td>
            <td class="ltr">{{ $row['currency'] }}</td>
            <td class="ltr">{{ number_format($row['total_ordered'], 2) }}</td>
            <td class="ltr">{{ number_format($row['total_paid'], 2) }}</td>
            <td class="ltr">{{ number_format($row['total_adjusted'], 2) }}</td>
            <td class="ltr">{{ number_format($row['balance'], 2) }}</td>
            <td class="ltr">{{ $row['movement_count'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
</body>
</html>
