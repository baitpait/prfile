<?php

namespace App\Http\Controllers;

use App\Models\Invoice;

class InvoicePrintController extends Controller
{
    public function show(Invoice $invoice)
    {
        $invoice->load(['lines', 'client.payments']);

        $client = $invoice->client;
        $totalPaid = $client ? $client->payments->sum('amount') : 0;
        $netAmount = (float) $invoice->total_amount - (float) ($invoice->discount_amount ?? 0);
        $balanceDue = $netAmount - $totalPaid;

        $amountInWords = $this->toArabicWords($netAmount, $invoice->currency_code ?? 'ILS');

        return view('invoices.print', compact('invoice', 'client', 'totalPaid', 'balanceDue', 'amountInWords'));
    }

    private function toArabicWords(float $amount, string $currency): string
    {
        $int = (int) floor($amount);
        $dec = (int) round(($amount - $int) * 100);

        $words = $this->intWords($int);

        $currencyMain = match ($currency) {
            'ILS' => ['شيكل جديد', 'أغورة'],
            'USD' => ['دولار أمريكي', 'سنت'],
            'JOD' => ['دينار أردني', 'فلس'],
            'EUR' => ['يورو', 'سنت'],
            default => [$currency, ''],
        };

        $result = 'فقط '.$words.' '.$currencyMain[0];
        if ($dec > 0 && $currencyMain[1]) {
            $result .= ' و'.$this->intWords($dec).' '.$currencyMain[1];
        }

        return $result.' لا غير';
    }

    private function intWords(int $n): string
    {
        if ($n === 0) {
            return 'صفر';
        }

        $ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة',
            'عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر',
            'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
        $tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $hundreds = ['', 'مئة', 'مئتان', 'ثلاثمئة', 'أربعمئة', 'خمسمئة', 'ستمئة', 'سبعمئة', 'ثمانمئة', 'تسعمئة'];

        $parts = [];

        if ($n >= 1000000) {
            $m = (int) ($n / 1000000);
            $parts[] = $this->intWords($m).' مليون';
            $n %= 1000000;
        }

        if ($n >= 1000) {
            $t = (int) ($n / 1000);
            if ($t === 1) {
                $parts[] = 'ألف';
            } elseif ($t === 2) {
                $parts[] = 'ألفان';
            } elseif ($t <= 10) {
                $parts[] = $ones[$t].' آلاف';
            } else {
                $parts[] = $this->intWords($t).' ألف';
            }
            $n %= 1000;
        }

        if ($n >= 100) {
            $parts[] = $hundreds[(int) ($n / 100)];
            $n %= 100;
        }

        if ($n > 0) {
            if ($n < 20) {
                $parts[] = $ones[$n];
            } else {
                $t = (int) ($n / 10);
                $o = $n % 10;
                $parts[] = $o > 0 ? $ones[$o].' و'.$tens[$t] : $tens[$t];
            }
        }

        return implode(' و', $parts);
    }
}
