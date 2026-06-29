<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@include('pdf.reports.partials.styles')
</head>
<body>
@include('pdf.reports.partials.header')
@if($truncated)
<p style="font-size:9pt;color:#92400E;margin-bottom:8pt;">يُعرض {{ $rows->count() }} من {{ $total }} سجل.</p>
@endif
@if($rows->isEmpty())
<p class="empty">لا توجد حركات.</p>
@else
<table class="main-table">
    <thead>
        <tr>
            <th class="ltr">التاريخ</th>
            <th>النوع</th>
            <th>الطرف</th>
            <th>المرجع</th>
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
            <td class="ltr">{{ $row['currency'] }}</td>
            <td class="ltr">{{ number_format($row['amount'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
</body>
</html>
