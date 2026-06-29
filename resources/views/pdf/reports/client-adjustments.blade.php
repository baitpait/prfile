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
    @foreach($totals as $cur => $total)
    <div class="summary-row"><span>إجمالي {{ $cur }}</span><span>{{ number_format($total, 2) }}</span></div>
    @endforeach
</div>
@endif
@if($rows->isEmpty())
<p class="empty">لا توجد تسويات.</p>
@else
<table class="main-table">
    <thead><tr><th class="ltr">التاريخ</th><th>العميل</th><th>النوع</th><th>السبب</th><th class="ltr">عملة</th><th class="ltr">المبلغ</th></tr></thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td class="ltr">{{ $row['date']->format('d/m/Y') }}</td>
            <td>{{ $row['client_name'] }}</td>
            <td>{{ $row['type_label'] }}</td>
            <td>{{ $row['reason'] ?? '—' }}</td>
            <td class="ltr">{{ $row['currency'] }}</td>
            <td class="ltr">{{ number_format($row['amount'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
</body>
</html>
