<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\ClientStatementService;

class InvoicePrintController extends Controller
{
    public function show(Invoice $invoice)
    {
        $invoice->load(['lines', 'client']);

        $client = $invoice->client;
        $currencyCode = $invoice->currency_code ?? 'ILS';
        $clientBalanceDue = $this->clientBalanceDue($client, $currencyCode, $invoice);

        $amountInWords = $this->toArabicWords(
            (float) $invoice->total_amount,
            $currencyCode
        );

        return view('invoices.print', compact('invoice', 'client', 'amountInWords', 'clientBalanceDue'));
    }

    /**
     * Business Purpose: On print, show total amount due only when the client had prior balance
     * in this currency (not only the current invoice).
     */
    private function clientBalanceDue(?\App\Models\Client $client, string $currencyCode, Invoice $invoice): ?float
    {
        if ($client === null) {
            return null;
        }

        $statement = (new ClientStatementService)->forClient($client);
        $balance = (float) ($statement[$currencyCode]['balance'] ?? 0);

        if ($balance <= 0.00001) {
            return null;
        }

        $priorBalance = $balance;
        if ($invoice->status === 'issued' && $invoice->currency_code === $currencyCode) {
            $priorBalance = $balance - (float) $invoice->total_amount;
        }

        if ($priorBalance <= 0.00001) {
            return null;
        }

        return round($balance, 2);
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
