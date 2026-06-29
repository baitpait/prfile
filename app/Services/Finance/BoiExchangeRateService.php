<?php

namespace App\Services\Finance;

use App\Models\ExchangeRate;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Business Purpose: Official Bank of Israel representative rates (ILS per foreign unit) for P&L consolidation.
 */
class BoiExchangeRateService
{
    private const BOI_JSON_URL = 'https://www.boi.org.il/PublicApi/GetExchangeRates?asXml=false';

    private const SDMX_BASE = 'https://edge.boi.gov.il/FusionEdgeServer/sdmx/v2/data/dataflow/BOI.STATISTICS/EXR/1.0';

    /** @return list<string> */
    public function supportedCurrencies(): array
    {
        return array_values(array_filter(
            Product::billingCurrencies(),
            fn (string $c): bool => $c !== 'ILS'
        ));
    }

    /**
     * Fetch today's BOI rates and upsert into exchange_rates.
     *
     * @return array<string, float> currency => rate_to_ils
     */
    public function fetchAndStoreToday(): array
    {
        $response = Http::timeout(20)->get(self::BOI_JSON_URL);

        if (! $response->successful()) {
            throw new RuntimeException('فشل جلب أسعار بنك إسرائيل: HTTP '.$response->status());
        }

        $payload = $response->json();
        $rows = $payload['exchangeRates'] ?? [];

        if (! is_array($rows) || $rows === []) {
            throw new RuntimeException('استجابة بنك إسرائيل غير متوقعة.');
        }

        $date = now()->toDateString();
        $stored = [];

        foreach ($rows as $row) {
            $code = strtoupper((string) ($row['key'] ?? ''));
            if (! in_array($code, $this->supportedCurrencies(), true)) {
                continue;
            }

            $unit = max(1, (int) ($row['unit'] ?? 1));
            $rate = round((float) ($row['currentExchangeRate'] ?? 0) / $unit, 6);

            if ($rate <= 0) {
                continue;
            }

            ExchangeRate::query()->updateOrCreate(
                ['rate_date' => $date, 'currency_code' => $code],
                ['rate_to_ils' => $rate, 'source' => 'BOI']
            );

            $stored[$code] = $rate;
        }

        return $stored;
    }

    /**
     * ILS per one unit of foreign currency on or before the given date.
     */
    public function getRateToIls(string $currencyCode, Carbon $date): float
    {
        $currencyCode = strtoupper($currencyCode);

        if ($currencyCode === 'ILS') {
            return 1.0;
        }

        $cached = ExchangeRate::query()
            ->where('currency_code', $currencyCode)
            ->whereDate('rate_date', '<=', $date->toDateString())
            ->orderByDesc('rate_date')
            ->first();

        if ($cached !== null) {
            return (float) $cached->rate_to_ils;
        }

        $fetched = $this->fetchHistoricalFromSdmx($currencyCode, $date);

        if ($fetched !== null) {
            ExchangeRate::query()->updateOrCreate(
                ['rate_date' => $date->toDateString(), 'currency_code' => $currencyCode],
                ['rate_to_ils' => $fetched, 'source' => 'BOI']
            );

            return $fetched;
        }

        $todayRates = $this->fetchAndStoreToday();

        if (isset($todayRates[$currencyCode])) {
            return $todayRates[$currencyCode];
        }

        throw new RuntimeException("لا يوجد سعر صرف لـ {$currencyCode}.");
    }

    private function fetchHistoricalFromSdmx(string $currencyCode, Carbon $date): ?float
    {
        $day = $date->format('Y-m-d');
        $url = self::SDMX_BASE.'/RER_'.$currencyCode.'_ILS'
            .'?startperiod='.$day.'&endperiod='.$day.'&format=csv';

        $response = Http::timeout(20)->get($url);

        if (! $response->successful()) {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($response->body())) ?: [];

        foreach ($lines as $line) {
            if ($line === '' || str_starts_with($line, 'DATAFLOW') || str_starts_with($line, '"DATAFLOW')) {
                continue;
            }

            $parts = str_getcsv($line);
            $value = (float) ($parts[count($parts) - 1] ?? 0);

            if ($value > 0) {
                return round($value, 6);
            }
        }

        return null;
    }
}
