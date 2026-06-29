<?php

namespace App\Services\Reports;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Business Purpose: Shared date/currency filters for period-based financial reports.
 */
readonly class ReportPeriodFilters
{
    /** @param list<string> $validMethods */
    public function __construct(
        public ?Carbon $dateFrom,
        public ?Carbon $dateTo,
        public ?string $currency = null,
        public ?string $method = null,
        public ?int $clientId = null,
        public ?int $supplierId = null,
        public array $validMethods = ['cash', 'bank', 'check', 'transfer'],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->all());
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $dateFrom = self::parseDate($data['date_from'] ?? $data['dateFrom'] ?? null);
        $dateTo = self::parseDate($data['date_to'] ?? $data['dateTo'] ?? null);

        $currency = strtoupper(trim((string) ($data['currency'] ?? '')));
        if ($currency === '' || ! in_array($currency, Product::billingCurrencies(), true)) {
            $currency = null;
        }

        $method = trim((string) ($data['method'] ?? ''));
        $validMethods = ['cash', 'bank', 'check', 'transfer'];
        if ($method === '' || ! in_array($method, $validMethods, true)) {
            $method = null;
        }

        $clientId = isset($data['client_id']) && $data['client_id'] !== '' ? (int) $data['client_id'] : null;
        $supplierId = isset($data['supplier_id']) && $data['supplier_id'] !== '' ? (int) $data['supplier_id'] : null;

        return new self($dateFrom, $dateTo, $currency, $method, $clientId, $supplierId);
    }

    public function resolvedDateFrom(): Carbon
    {
        return ($this->dateFrom ?? now()->startOfMonth())->copy()->startOfDay();
    }

    public function resolvedDateTo(): Carbon
    {
        return ($this->dateTo ?? now())->copy()->endOfDay();
    }

    /** @return array<string, string> */
    public function queryParams(): array
    {
        return array_filter([
            'date_from' => $this->dateFrom?->format('Y-m-d'),
            'date_to' => $this->dateTo?->format('Y-m-d'),
            'currency' => $this->currency,
            'method' => $this->method,
            'client_id' => $this->clientId ? (string) $this->clientId : null,
            'supplier_id' => $this->supplierId ? (string) $this->supplierId : null,
        ], fn (?string $v): bool => $v !== null && $v !== '');
    }

    /** @return list<string> */
    public function describeActive(): array
    {
        $labels = [];
        $labels[] = 'الفترة: '.$this->resolvedDateFrom()->format('d/m/Y').' — '.$this->resolvedDateTo()->format('d/m/Y');

        if ($this->currency !== null) {
            $labels[] = 'العملة: '.$this->currency;
        }

        if ($this->method !== null) {
            $labels[] = 'طريقة الدفع: '.PaymentMethodLabels::label($this->method);
        }

        if ($this->clientId !== null) {
            $labels[] = 'عميل #'.$this->clientId;
        }

        if ($this->supplierId !== null) {
            $labels[] = 'مورد #'.$this->supplierId;
        }

        return $labels;
    }

    private static function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
