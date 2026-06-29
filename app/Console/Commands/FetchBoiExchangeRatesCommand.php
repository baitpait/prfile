<?php

namespace App\Console\Commands;

use App\Services\Finance\BoiExchangeRateService;
use Illuminate\Console\Command;

class FetchBoiExchangeRatesCommand extends Command
{
    protected $signature = 'boi:fetch-rates';

    protected $description = 'Fetch Bank of Israel representative exchange rates and store for today';

    public function handle(BoiExchangeRateService $service): int
    {
        try {
            $rates = $service->fetchAndStoreToday();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($rates === []) {
            $this->warn('لم يُخزَّن أي سعر — تحقق من استجابة بنك إسرائيل.');

            return self::FAILURE;
        }

        foreach ($rates as $currency => $rate) {
            $this->line("{$currency} → {$rate} ILS");
        }

        $this->info('تم حفظ '.count($rates).' سعر صرف.');

        return self::SUCCESS;
    }
}
