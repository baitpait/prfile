<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            color: #3D3D3D;
            font-size: 11pt;
            direction: rtl;
        }
        .page { padding: 20mm 15mm; }

        /* ترويسة */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16pt; border-bottom: 2px solid #C9A227; padding-bottom: 10pt; }
        .header-brand { font-size: 18pt; font-weight: bold; color: #3D3D3D; }
        .header-brand small { font-size: 9pt; color: #C9A227; display: block; margin-top: 2pt; }
        .header-meta { text-align: left; font-size: 9pt; color: #666; }

        /* عنوان الكشف */
        .statement-title { font-size: 16pt; font-weight: bold; margin-bottom: 4pt; }
        .client-name { font-size: 13pt; color: #C9A227; margin-bottom: 14pt; }

        /* فلتر التاريخ */
        .date-range { font-size: 9pt; color: #666; margin-bottom: 14pt; }

        /* قسم العملة */
        .currency-section { margin-bottom: 18pt; page-break-inside: avoid; }
        .currency-header { background: #F5F5F5; border: 1px solid #E0E0E0; padding: 6pt 10pt; font-size: 12pt; font-weight: bold; }
        .currency-code { color: #C9A227; direction: ltr; display: inline-block; }

        /* جداول */
        table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-top: 6pt; }
        th { background: #F5F5F5; text-align: right; padding: 5pt 8pt; border: 1px solid #E0E0E0; font-weight: 600; }
        td { padding: 5pt 8pt; border: 1px solid #E0E0E0; }
        .ltr { direction: ltr; text-align: left; font-family: 'DejaVu Sans Mono', monospace; }
        tfoot td { background: #F5F5F5; font-weight: bold; }

        /* ملخص الرصيد */
        .balance-box { margin-top: 8pt; border: 1px solid #E0E0E0; padding: 8pt 12pt; width: 260pt; float: left; }
        .balance-row { display: flex; justify-content: space-between; font-size: 9.5pt; margin-bottom: 3pt; }
        .balance-total { border-top: 1px solid #E0E0E0; padding-top: 5pt; margin-top: 4pt; font-weight: bold; font-size: 11pt; }
        .balance-owed { color: #DC2626; }
        .balance-clear { color: #16A34A; }
        .clearfix::after { content: ''; display: table; clear: both; }

        /* تذييل */
        .footer { border-top: 1px solid #E0E0E0; padding-top: 8pt; margin-top: 20pt; font-size: 8pt; color: #999; }
    </style>
</head>
<body>
<div class="page">

    {{-- ترويسة --}}
    <div class="header">
        <div class="header-brand">
            بروفايل ميدا
            <small>إنتاج إعلامي وتقارير تشغيلية</small>
        </div>
        <div class="header-meta">
            تاريخ الإصدار: {{ now()->format('Y-m-d') }}<br>
            الوقت (UTC): {{ now()->utc()->format('H:i') }}
        </div>
    </div>

    {{-- عنوان الكشف --}}
    <div class="statement-title">كشف حساب</div>
    <div class="client-name">{{ $client->displayName() }}</div>

    @if($dateFrom || $dateTo)
    <div class="date-range">
        الفترة:
        @if($dateFrom) من {{ $dateFrom }} @endif
        @if($dateTo) إلى {{ $dateTo }} @endif
    </div>
    @endif

    @if(empty($statement))
        <p style="color:#999; text-align:center; margin-top:40pt;">لا توجد حركات مالية في هذه الفترة.</p>
    @endif

    {{-- قسم لكل عملة --}}
    @foreach($statement as $currency => $section)
    <div class="currency-section">
        <div class="currency-header">
            عملة: <span class="currency-code">{{ $currency }}</span>
        </div>

        {{-- فواتير --}}
        @if($section['invoices']->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>رقم الفاتورة</th>
                    <th>الحالة</th>
                    <th class="ltr">المبلغ ({{ $currency }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($section['invoices'] as $inv)
                <tr>
                    <td class="ltr">{{ $inv->document_date->format('Y-m-d') }}</td>
                    <td>{{ $inv->legacy_invoice_no ?? '#'.$inv->id }}</td>
                    <td>{{ $inv->status === 'issued' ? 'صادرة' : ($inv->status === 'draft' ? 'مسودة' : 'ملغاة') }}</td>
                    <td class="ltr">{{ number_format((float)$inv->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">مجموع الفواتير</td>
                    <td class="ltr">{{ number_format($section['total_invoiced'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
        @endif

        {{-- دفعات --}}
        @if($section['payments']->count() > 0)
        <table style="margin-top:6pt;">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>طريقة الدفع</th>
                    <th>المرجع</th>
                    <th class="ltr">المبلغ ({{ $currency }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($section['payments'] as $pay)
                <tr>
                    <td class="ltr">{{ $pay->paid_at->format('Y-m-d') }}</td>
                    <td>{{ $pay->method ?? '—' }}</td>
                    <td>{{ $pay->bank_reference ?? '—' }}</td>
                    <td class="ltr">{{ number_format((float)$pay->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">مجموع الدفعات</td>
                    <td class="ltr">{{ number_format($section['total_paid'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
        @endif

        {{-- ملخص الرصيد --}}
        <div class="clearfix">
            <div class="balance-box">
                <div class="balance-row">
                    <span>إجمالي الفواتير</span>
                    <span class="ltr">{{ number_format($section['total_invoiced'], 2) }} {{ $currency }}</span>
                </div>
                <div class="balance-row">
                    <span>إجمالي الدفعات</span>
                    <span class="ltr" style="color:#16A34A;">{{ number_format($section['total_paid'], 2) }} {{ $currency }}</span>
                </div>
                <div class="balance-row balance-total">
                    <span>الرصيد المستحق</span>
                    <span class="ltr {{ $section['balance'] > 0 ? 'balance-owed' : 'balance-clear' }}">
                        {{ number_format($section['balance'], 2) }} {{ $currency }}
                    </span>
                </div>
            </div>
        </div>

    </div>
    @endforeach

    {{-- تذييل --}}
    <div class="footer">
        <p>وثيقة داخلية — بروفايل ميدا للإنتاج الإعلامي &bull; صدر بتاريخ {{ now()->format('Y-m-d H:i') }} UTC</p>
    </div>

</div>
</body>
</html>
