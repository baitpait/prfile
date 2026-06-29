<?php

namespace App\Services\Reports;

class PaymentMethodLabels
{
    public static function label(?string $method): string
    {
        return match ($method) {
            'cash' => 'نقداً',
            'bank' => 'بنك',
            'check' => 'شيك',
            'transfer' => 'تحويل',
            default => $method !== null && $method !== '' ? $method : '—',
        };
    }
}
