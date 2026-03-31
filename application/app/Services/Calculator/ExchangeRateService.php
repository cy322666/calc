<?php

namespace App\Services\Calculator;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public function usdToRubForDate(?string $calculationDate = null): float
    {
        try {
            $date = $calculationDate
                ? now()->parse($calculationDate)
                : now();
        } catch (\Throwable) {
            $date = now();
        }

        $cacheKey = 'calc.usd_rub_rate.' . $date->format('Ymd');

        return Cache::remember($cacheKey, 60 * 60 * 24, function () use ($date): float {
            $rate = $this->fetchUsdRateForDate($date);

            if ($rate > 0) {
                return $rate;
            }

            return $this->fetchUsdRateLatest();
        });
    }

    public function usdToRub(): float
    {
        return $this->usdToRubForDate(now()->toDateString());
    }

    private function fetchUsdRateLatest(): float
    {
        return $this->fetchUsdRateFromResponse(
            Http::timeout(5)->get('https://www.cbr.ru/scripts/XML_daily.asp')->body()
        );
    }

    private function fetchUsdRateForDate(CarbonInterface $date): float
    {
        try {
            $response = Http::timeout(5)->get('https://www.cbr.ru/scripts/XML_daily.asp', [
                'date_req' => $date->format('d/m/Y'),
            ]);

            if (!$response->ok()) {
                return 0.0;
            }

            return $this->fetchUsdRateFromResponse($response->body());
        } catch (\Throwable $e) {
            Log::warning('Exchange rate fetch failed', [
                'error' => $e->getMessage(),
                'date' => $date->format('Y-m-d'),
            ]);
        }

        return 0.0;
    }

    private function fetchUsdRateFromResponse(string $xmlString): float
    {
        if ($xmlString === '') {
            return 0.0;
        }

        $xml = @simplexml_load_string($xmlString);
        if (!$xml) {
            return 0.0;
        }

        foreach ($xml->Valute as $valute) {
            if ((string) $valute->CharCode !== 'USD') {
                continue;
            }

            $value = (float) str_replace(',', '.', (string) $valute->Value);
            $nominal = (float) str_replace(',', '.', (string) $valute->Nominal);
            $nominal = $nominal > 0 ? $nominal : 1.0;

            $rate = $value / $nominal;
            return $rate > 0 ? round($rate, 4) : 0.0;
        }

        return 0.0;
    }
}
