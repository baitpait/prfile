<?php

namespace App\Services\Reports;

use App\Services\Finance\PaymentMethod;

class PaymentMethodLabels
{
    public static function label(?string $method): string
    {
        return PaymentMethod::label($method);
    }
}
