<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: xbriyaz, sans-serif;
    color: #3D3D3D;
    font-size: 10pt;
    direction: rtl;
}

/* ===== HEADER ===== */
.header {
    width: 100%;
    margin-bottom: 14pt;
    overflow: hidden;
}
.header-right {
    float: right;
}
.header-title {
    font-size: 22pt;
    font-weight: bold;
    color: #1a1a1a;
}
.header-title span {
    font-size: 14pt;
    font-weight: normal;
    color: #3D3D3D;
}
.header-left {
    float: left;
    text-align: left;
    direction: ltr;
}
.header-logo {
    width: 90pt;
    display: block;
    margin-bottom: 4pt;
}
.header-company {
    font-size: 10pt;
    font-weight: bold;
    color: #3D3D3D;
    direction: ltr;
    text-align: center;
}
.header-date {
    font-size: 9pt;
    color: #555;
    direction: ltr;
    text-align: center;
    margin-top: 3pt;
}

/* ===== SECTION TITLE ===== */
.section-title {
    font-size: 12pt;
    font-weight: bold;
    text-align: right;
    margin-bottom: 6pt;
    margin-top: 4pt;
}

/* ===== MAIN TABLE ===== */
.main-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
}
.main-table th {
    background: #E8E8E8;
    border: 1pt solid #CCCCCC;
    padding: 5pt 7pt;
    font-weight: bold;
    text-align: right;
}
.main-table th.ltr { direction: ltr; text-align: left; }

/* صف الفاتورة - رأس */
.row-invoice-header td {
    border: 1pt solid #CCCCCC;
    padding: 5pt 7pt;
    font-weight: bold;
    background: #FAFAFA;
}
.row-invoice-header .inv-number {
    font-weight: bold;
    font-size: 10pt;
}

/* صف تفاصيل الفاتورة (جدول داخلي) */
.row-invoice-lines td {
    border-right: 1pt solid #CCCCCC;
    border-left: 1pt solid #CCCCCC;
    padding: 0;
}
.lines-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8.5pt;
}
.lines-table th {
    background: #C9A227;
    color: #fff;
    padding: 4pt 6pt;
    text-align: right;
    font-weight: bold;
    border-right: 1pt solid #b8911e;
}
.lines-table th:first-child { border-right: none; }
.lines-table td {
    padding: 4pt 6pt;
    border-top: 1pt solid #E8E8E8;
    border-right: 1pt solid #E8E8E8;
    text-align: right;
    background: #fff;
}
.lines-table td.ltr { direction: ltr; text-align: left; }

/* صف إجمالي الفاتورة */
.row-invoice-total td {
    border: 1pt solid #CCCCCC;
    padding: 4pt 7pt;
    background: #F5F5F5;
    font-weight: bold;
    direction: ltr;
    text-align: left;
}
.row-invoice-total .label-cell {
    direction: rtl;
    text-align: right;
    font-size: 8.5pt;
    color: #555;
}

/* صف الدفعة */
.row-payment td {
    border: 1pt solid #CCCCCC;
    padding: 5pt 7pt;
    background: #FFFDF5;
}
.row-payment .pay-desc {
    font-weight: bold;
    color: #1a1a1a;
}
.row-payment .amount-col {
    direction: ltr;
    text-align: left;
    color: #DC2626;
    font-weight: bold;
}
.row-payment .balance-col {
    direction: ltr;
    text-align: left;
    font-weight: bold;
}

/* صف رصيد نهاية المدة */
.row-final td {
    border: 1pt solid #CCCCCC;
    padding: 6pt 7pt;
    background: #3D3D3D;
    color: #fff;
    font-weight: bold;
    font-size: 10pt;
}
.row-final .balance-col {
    direction: ltr;
    text-align: left;
    font-size: 11pt;
}

.ltr  { direction: ltr; text-align: left; }
.rtl  { direction: rtl; text-align: right; }
.amt  { direction: ltr; text-align: left; font-weight: bold; }
.amt-negative { color: #DC2626; }
.amt-positive { color: #1a1a1a; }

/* ===== FOOTER ===== */
.footer {
    margin-top: 18pt;
    text-align: center;
}
.footer img {
    width: 70pt;
}

/* currency section spacing */
.currency-block { margin-bottom: 24pt; }
</style>
</head>
<body>

@php
    $logoDataFile = resource_path('views/pdf/logo-data.php');
    $logoData = file_exists($logoDataFile)
        ? require $logoDataFile
        : (file_exists(public_path('branding/logo.png'))
            ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('branding/logo.png')))
            : null);

    $printDate = $dateTo
        ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y')
        : now()->format('d/m/Y');

    $methods = ['cash' => 'Cash', 'bank' => 'Bank', 'check' => 'Check', 'transfer' => 'Transfer'];
@endphp

{{-- ===== HEADER ===== --}}
<div class="header">
    <div class="header-right">
        <div class="header-title">
            كشف حساب&nbsp; <span>{{ $client->displayName() }}</span>
        </div>
    </div>
    <div class="header-left">
        @if($logoData)
            <img src="{{ $logoData }}" class="header-logo" alt="Logo">
        @endif
        <div class="header-company">Profile Media Production</div>
        <div class="header-date">Date: {{ $printDate }}</div>
    </div>
    <div style="clear:both;"></div>
</div>

@if(empty($statement))
    <p style="color:#999; text-align:center; margin-top:40pt;">لا توجد حركات مالية في هذه الفترة.</p>
@endif

{{-- ===== قسم لكل عملة ===== --}}
@foreach($statement as $currency => $section)
<div class="currency-block">

    <div class="section-title">حركة الحساب حتى {{ $printDate }}</div>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width:70pt;">التاريخ</th>
                <th>العملية</th>
                <th style="width:80pt;" class="ltr">المبلغ {{ $currency }}</th>
                <th style="width:80pt;" class="ltr">المبلغ المستحق</th>
            </tr>
        </thead>
        <tbody>

        @foreach($section['timeline'] as $event)

            @if($event['type'] === 'invoice')
            @php $inv = $event['model']; $invNo = $inv->legacy_invoice_no ?? ('#' . $inv->id); @endphp

            {{-- رأس الفاتورة --}}
            <tr class="row-invoice-header">
                <td class="ltr" style="font-size:8.5pt;">{{ $event['date']->format('d/m/Y') }}</td>
                <td colspan="2">
                    <span class="inv-number">فاتورة {{ $invNo }}#</span>
                </td>
                <td class="ltr" style="font-weight:bold;">
                    {{ number_format($event['running_balance'], 2) }} ش.ج
                </td>
            </tr>

            {{-- بنود الفاتورة --}}
            @if($inv->lines->count() > 0)
            <tr class="row-invoice-lines">
                <td colspan="4">
                    <table class="lines-table">
                        <thead>
                            <tr>
                                <th style="width:80pt;">السعر X الكمية</th>
                                <th>البند</th>
                                <th style="width:70pt;">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inv->lines as $line)
                            <tr>
                                <td class="ltr" style="white-space:nowrap;">
                                    {{ number_format((float)$line->unit_price, 2) }}
                                    x {{ number_format((float)$line->quantity, 0) }}
                                </td>
                                <td>{{ $line->title }}{{ $line->description ? ' - ' . $line->description : '' }}</td>
                                <td class="ltr">{{ number_format((float)$line->line_total, 2) }} ش.ج</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
            @endif

            {{-- إجمالي الفاتورة --}}
            <tr class="row-invoice-total">
                <td colspan="2" class="label-cell">
                    {{ number_format($event['amount'], 2) }} ش.ج
                </td>
                <td class="ltr">
                    <strong>{{ $currency }}</strong>
                    {{ number_format($event['amount'], 2) }} ش.ج
                </td>
                <td class="ltr"></td>
            </tr>

            @else
            {{-- ===== صف الدفعة ===== --}}
            @php
                $pay = $event['model'];
                $payNo = $pay->bank_reference ?? ('#' . str_pad($pay->id, 6, '0', STR_PAD_LEFT));
                $methodLabel = $methods[$pay->method] ?? $pay->method ?? '';
            @endphp
            <tr class="row-payment">
                <td class="ltr" style="font-size:8.5pt;">{{ $event['date']->format('d/m/Y') }}</td>
                <td>
                    <span class="pay-desc">
                        عملية الدفع {{ $payNo }}#
                        @if($methodLabel) ({{ $methodLabel }})@endif
                    </span>
                </td>
                <td class="ltr amt-negative">
                    ({{ number_format($event['amount'], 2) }} ش.ج)
                    <span style="font-size:8pt;font-weight:normal;color:#888;"> {{ $currency }}</span>
                </td>
                <td class="ltr" style="font-weight:bold;
                    {{ $event['running_balance'] > 0 ? 'color:#DC2626;' : ($event['running_balance'] < 0 ? 'color:#16A34A;' : '') }}">
                    {{ number_format($event['running_balance'], 2) }} ش.ج
                </td>
            </tr>
            @endif

        @endforeach

        {{-- ===== رصيد نهاية المدة ===== --}}
        <tr class="row-final">
            <td colspan="3" class="rtl">رصيد نهاية المدة</td>
            <td class="balance-col ltr">
                {{ number_format($section['balance'], 2) }} ش.ج
            </td>
        </tr>

        </tbody>
    </table>

</div>
@endforeach

{{-- ===== FOOTER ===== --}}
<div class="footer">
    @if($logoData)
        <img src="{{ $logoData }}" alt="Profile Media Production">
    @endif
</div>

</body>
</html>
