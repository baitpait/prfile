<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: xbriyaz, sans-serif; color: #3D3D3D; font-size: 10pt; direction: rtl; }
.header { width: 100%; margin-bottom: 14pt; overflow: hidden; }
.header-right { float: right; }
.header-title { font-size: 20pt; font-weight: bold; color: #1a1a1a; }
.header-subtitle { font-size: 10pt; color: #555; margin-top: 4pt; }
.header-left { float: left; text-align: left; direction: ltr; }
.header-logo { width: 90pt; display: block; margin-bottom: 4pt; }
.header-company { font-size: 10pt; font-weight: bold; color: #3D3D3D; direction: ltr; text-align: center; }
.header-date { font-size: 9pt; color: #555; direction: ltr; text-align: center; margin-top: 3pt; }
.filters { margin-bottom: 10pt; font-size: 9pt; color: #555; }
.filters span { display: inline-block; background: #F5F5F5; border: 1pt solid #E0E0E0; padding: 2pt 6pt; margin: 2pt 0 2pt 4pt; border-radius: 2pt; }
.totals { margin-bottom: 10pt; font-size: 9pt; }
.totals span { margin-left: 10pt; }
.main-table { width: 100%; border-collapse: collapse; font-size: 9pt; }
.main-table th { background: #E8E8E8; border: 1pt solid #CCC; padding: 5pt 7pt; font-weight: bold; text-align: right; }
.main-table th.ltr, .main-table td.ltr { direction: ltr; text-align: left; }
.main-table td { border: 1pt solid #CCC; padding: 5pt 7pt; text-align: right; }
.main-table tr:nth-child(even) td { background: #FAFAFA; }
.empty-msg { color: #999; text-align: center; margin: 30pt 0; }
.overdue { color: #B91C1C; font-weight: bold; }
</style>
</head>
<body>

@php
    $logoPath = public_path('branding/logo.png');
    $logoExists = file_exists($logoPath);
@endphp

<div class="header">
    <div class="header-right">
        <div class="header-title">تقرير استحقاق الشيكات</div>
        <div class="header-subtitle">شيكات «قيد المعالجة» فقط — حسب تاريخ الاستحقاق</div>
    </div>
    <div class="header-left">
        @if($logoExists)
            <img src="{{ $logoPath }}" class="header-logo" alt="Logo">
        @endif
        <div class="header-company">{{ $companyName }}</div>
        <div class="header-date">Date: {{ $printedAt }}</div>
    </div>
    <div style="clear:both;"></div>
</div>

@if(count($filterLabels) > 0)
<div class="filters">
    <strong>الفلاتر:</strong>
    @foreach($filterLabels as $label)
        <span>{{ $label }}</span>
    @endforeach
</div>
@endif

@if(!empty($totalsByCurrency))
<div class="totals">
    <strong>الإجماليات:</strong>
    @foreach($totalsByCurrency as $cur => $box)
        <span dir="ltr">{{ $cur }}: {{ number_format($box['total'], 2) }} ({{ $box['count'] }} شيك)</span>
    @endforeach
</div>
@endif

@if($rows->isEmpty())
    <p class="empty-msg">لا توجد شيكات pending مطابقة للفلتر.</p>
@else
    <table class="main-table">
        <thead>
            <tr>
                <th>العميل</th>
                <th class="ltr">رقم الشيك</th>
                <th class="ltr">الاستحقاق</th>
                <th class="ltr">أيام</th>
                <th>الفترة</th>
                <th class="ltr">المبلغ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            @php
                $days = $service->daysUntilDue($row);
                $bucketCode = $service->bucketForCheck($row);
            @endphp
            <tr>
                <td>{{ $row->client?->displayName() ?? '—' }}</td>
                <td class="ltr">{{ $row->check_number }}</td>
                <td class="ltr">{{ $row->due_date?->format('Y-m-d') ?? '—' }}</td>
                <td class="ltr {{ $days < 0 ? 'overdue' : '' }}">{{ $days }}</td>
                <td>{{ $bucketOptions[$bucketCode] ?? '—' }}</td>
                <td class="ltr">{{ number_format((float) $row->amount, 2) }} {{ $row->currency_code }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

</body>
</html>
