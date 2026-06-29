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
<p class="empty">لا توجد ذمم مستحقة.</p>
@else
<table class="main-table">
    <thead><tr><th>العميل</th><th class="ltr">الهاتف</th><th class="ltr">عملة</th><th class="ltr">فواتير</th><th class="ltr">دفعات</th><th class="ltr">تسويات</th><th class="ltr">الرصيد</th></tr></thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td>{{ $row['client_name'] }}</td>
            <td class="ltr">{{ $row['phone'] ?? '—' }}</td>
            <td class="ltr">{{ $row['currency'] }}</td>
            <td class="ltr">{{ number_format($row['total_invoiced'], 2) }}</td>
            <td class="ltr">{{ number_format($row['total_paid'], 2) }}</td>
            <td class="ltr">{{ number_format($row['total_adjusted'], 2) }}</td>
            <td class="ltr">{{ number_format($row['balance'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
</body>
</html>
