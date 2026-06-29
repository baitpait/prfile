<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@include('pdf.reports.partials.styles')
</head>
<body>

@include('pdf.reports.partials.header')

@if(!empty($summary))
@foreach($summary as $cur => $s)
<div class="summary-box">
    <div class="summary-row"><span>عملة {{ $cur }} — وارد (عملاء)</span><span class="positive">+{{ number_format($s['inflow'], 2) }}</span></div>
    <div class="summary-row"><span>صادر (موردين)</span><span class="negative">−{{ number_format($s['supplier_outflow'], 2) }}</span></div>
    <div class="summary-row"><span>صادر (مصروفات)</span><span class="negative">−{{ number_format($s['expense_outflow'], 2) }}</span></div>
    <div class="summary-row"><span><strong>صافي الفترة</strong></span><span><strong>{{ number_format($s['net'], 2) }}</strong></span></div>
</div>
@endforeach
@endif

@if($rows->isEmpty())
<p class="empty">لا توجد حركات نقدية في هذه الفترة.</p>
@else
<table class="main-table">
    <thead>
        <tr>
            <th class="ltr">التاريخ</th>
            <th>النوع</th>
            <th>الطرف</th>
            <th>المرجع</th>
            <th>طريقة الدفع</th>
            <th class="ltr">عملة</th>
            <th class="ltr">المبلغ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td class="ltr">{{ $row['date']->format('d/m/Y') }}</td>
            <td>{{ $row['type_label'] }}</td>
            <td>{{ $row['party'] }}</td>
            <td>{{ $row['reference'] }}</td>
            <td>{{ $row['method_label'] }}</td>
            <td class="ltr">{{ $row['currency'] }}</td>
            <td class="ltr {{ $row['signed_amount'] >= 0 ? 'positive' : 'negative' }}">{{ $row['signed_amount'] >= 0 ? '+' : '' }}{{ number_format($row['signed_amount'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

</body>
</html>
