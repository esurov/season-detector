<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    private const CACHE_DURATION_SECONDS = 3600;

    private const LOOKBACK_DAYS = 14;

    private const SPRING_THRESHOLD = 7.0;

    /**
     * Fetch the last 14 days of mean daily temperatures and compute the average.
     *
     * @return array{daily_temperatures: float[], average_temperature: float}
     *
     * @throws \RuntimeException
     */
    public function getTemperatureData(float $latitude, float $longitude): array
    {
        $cacheKey = $this->buildCacheKey($latitude, $longitude);

        return Cache::remember($cacheKey, self::CACHE_DURATION_SECONDS, function () use ($latitude, $longitude): array {
            $dailyTemperatures = $this->fetchDailyTemperatures($latitude, $longitude);
            $average = $this->calculateAverage($dailyTemperatures);

            return [
                'daily_temperatures' => $dailyTemperatures,
                'average_temperature' => round($average, 2),
            ];
        });
    }

    public function determineSeason(float $averageTemperature): string
    {
        return $averageTemperature > self::SPRING_THRESHOLD ? 'Spring!' : 'Winter!';
    }

    public function calculateAverage(array $temperatures): float
    {
        if (count($temperatures) === 0) {
            return 0.0;
        }

        return array_sum($temperatures) / count($temperatures);
    }

    /**
     * @return float[]
     *
     * @throws \RuntimeException
     */
    private function fetchDailyTemperatures(float $latitude, float $longitude): array
    {
        $endDate = now()->subDay()->format('Y-m-d');
        $startDate = now()->subDays(self::LOOKBACK_DAYS)->format('Y-m-d');

        $baseUrl = config('services.open_meteo.base_url');

        try {
            $response = Http::baseUrl($baseUrl)
                ->timeout(10)
                ->retry(3, 500, throw: false)
                ->get('/v1/forecast', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'daily' => 'temperature_2m_mean',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'timezone' => 'auto',
                ]);
        } catch (ConnectionException|RequestException $e) {
            throw new \RuntimeException('Weather service is unreachable. Please try again later.');
        }

        if ($response->failed()) {
            throw new \RuntimeException('Weather service returned an error (HTTP '.$response->status().').');
        }

        $data = $response->json();

        $temperatures = $data['daily']['temperature_2m_mean'] ?? null;

        if (! is_array($temperatures) || count($temperatures) === 0) {
            throw new \RuntimeException('Weather service returned an unexpected payload.');
        }

        // Filter out null values that can occur for missing data points
        return array_values(array_filter($temperatures, fn ($t): bool => $t !== null));
    }

    private function buildCacheKey(float $latitude, float $longitude): string
    {
        $lat = round($latitude, 2);
        $lng = round($longitude, 2);

        return "weather:{$lat}:{$lng}";
    }
}
