<?php

namespace App\Services\Finance;

/**
 * Canonical payment method codes stored in `method` columns across payments.
 * Legacy imports and demo data may use Arabic labels or ERP ids — normalize on load/save.
 */
class PaymentMethod
{
    public const CASH = 'cash';

    public const BANK = 'bank';

    public const CHECK = 'check';

    public const TRANSFER = 'transfer';

    /** @var list<string> */
    public const CODES = [
        self::CASH,
        self::BANK,
        self::CHECK,
        self::TRANSFER,
    ];

    /** @return list<string> */
    public static function codes(): array
    {
        return self::CODES;
    }

    public static function validationRule(): string
    {
        return 'required|in:'.implode(',', self::CODES);
    }

    public static function label(?string $method): string
    {
        return match (self::normalize($method)) {
            self::CASH => 'نقداً',
            self::BANK => 'بنك',
            self::CHECK => 'شيك',
            self::TRANSFER => 'تحويل',
            default => '—',
        };
    }

    public static function isCanonical(?string $method): bool
    {
        return $method !== null && in_array($method, self::CODES, true);
    }

    /**
     * Map legacy Arabic text, ERP placeholders, or empty values to a canonical code.
     */
    public static function normalize(?string $method): string
    {
        if ($method === null || trim($method) === '') {
            return self::CASH;
        }

        $method = trim($method);

        if (self::isCanonical($method)) {
            return $method;
        }

        if (preg_match('/^طريقة\s*#(\d+)$/u', $method, $matches) === 1) {
            return match ((int) $matches[1]) {
                1 => self::CASH,
                2 => self::BANK,
                3 => self::CHECK,
                4 => self::TRANSFER,
                default => self::BANK,
            };
        }

        $lower = mb_strtolower($method);

        return match (true) {
            in_array($lower, ['cash', 'نقداً', 'نقدي', 'نقد'], true) => self::CASH,
            in_array($lower, ['bank', 'بنك', 'بنكي', 'bank_transfer', 'credit card', 'credit_card'], true) => self::BANK,
            in_array($lower, ['check', 'cheque', 'شيك'], true) => self::CHECK,
            in_array($lower, ['transfer', 'wire', 'تحويل', 'تحويل بنكي'], true) => self::TRANSFER,
            default => self::CASH,
        };
    }
}
