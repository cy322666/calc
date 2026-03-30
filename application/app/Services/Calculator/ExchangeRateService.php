<?php

namespace App\Services\Calculator;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public function usdToRub(): float
    {
        return Cache::remember('calc.usd_rub_rate', 60 * 60 * 6, function (): float {
            try {
                $response = Http::timeout(5)->get('https://www.cbr.ru/scripts/XML_daily.asp');
                if (!$response->ok()) {
                    return 0.0;
                }

                $xml = @simplexml_load_string($response->body());
                if (!$xml) {
                    return 0.0;
                }

                foreach ($xml->Valute as $valute) {
                    if ((string) $valute->CharCode === 'USD') {
                        $value = (string) $valute->Value;
                        $value = str_replace(',', '.', $value);
                        $rate = (float) $value;
                        return $rate > 0 ? $rate : 0.0;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Exchange rate fetch failed', ['error' => $e->getMessage()]);
            }

            return 0.0;
        });
    }
}
