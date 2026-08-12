<?php

namespace App\Services\Finance;

use App\Models\ReceivedCheck;

/**
 * Business Purpose: Canonical lifecycle codes for checks received from clients.
 */
class ReceivedCheckStatus
{
    public const PENDING = 'pending';

    public const CLEARED = 'cleared';

    public const NOT_CLEARED = 'not_cleared';

    /** Phase 2+: endorsed to supplier or employee */
    public const ENDORSED = 'endorsed';

    /** @return list<string> */
    public static function codes(): array
    {
        return [
            self::PENDING,
            self::CLEARED,
            self::NOT_CLEARED,
            self::ENDORSED,
        ];
    }

    /** @return list<string> Phase 1 actionable from list */
    public static function phase1Codes(): array
    {
        return [
            self::PENDING,
            self::CLEARED,
            self::NOT_CLEARED,
        ];
    }

    /** @return list<string> All statuses for filters */
    public static function filterCodes(): array
    {
        return [
            self::PENDING,
            self::CLEARED,
            self::NOT_CLEARED,
            self::ENDORSED,
        ];
    }

    public static function label(string $code): string
    {
        return match ($code) {
            self::CLEARED => 'صُرف',
            self::NOT_CLEARED => 'لم يُصرف',
            self::ENDORSED => 'حُوّل',
            default => 'قيد المعالجة',
        };
    }

    public static function endorsementLabel(ReceivedCheck $check): string
    {
        if ($check->endorsed_supplier_id) {
            return 'حُوّل لمورد';
        }

        if ($check->endorsed_employee_id) {
            if ($check->salary_advance_id) {
                return 'حُوّل لموظف (سلفة)';
            }

            return 'حُوّل لموظف (راتب)';
        }

        return 'حُوّل';
    }

    public static function badgeClass(string $code): string
    {
        return match ($code) {
            self::CLEARED => 'badge-green',
            self::NOT_CLEARED => 'badge-red',
            self::ENDORSED => 'badge-blue',
            default => 'badge-yellow',
        };
    }

    public static function validationRule(): string
    {
        return 'required|in:'.implode(',', self::phase1Codes());
    }
}
