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
    <div class="summary-row">
        <span>{{ $cur }} — {{ $t['count'] }} أمر شراء</span>
        <span>{{ number_format($t['total'], 2) }}</span>
    </div>
    @endforeach
</div>
@endif

@if($rows->isEmpty())
<p class="empty">لا توجد أوامر شراء صادرة في هذه الفترة.</p>
@else
<table class="main-table">
    <thead>
        <tr>
            <th class="ltr">التاريخ</th>
            <th>المورد</th>
            <th>رقم أمر الشراء</th>
            <th class="ltr">عملة</th>
            <th class="ltr">المبلغ</th>
            <th>حالة الدفع</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td class="ltr">{{ $row['date']->format('d/m/Y') }}</td>
            <td>{{ $row['supplier_name'] }}</td>
            <td>{{ $row['reference'] }}</td>
            <td class="ltr">{{ $row['currency'] }}</td>
            <td class="ltr">{{ number_format($row['amount'], 2) }}</td>
            <td>{{ $row['payment_label'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

</body>
</html>
