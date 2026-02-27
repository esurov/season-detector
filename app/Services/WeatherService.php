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
     * Fetch the last 14 days of daily temperature data and compute the average.
     *
     * @return array{daily_temperatures: array<int, array{date: string, mean: float, min: float|null, max: float|null}>, average_temperature: float}
     *
     * @throws \RuntimeException
     */
    public function getTemperatureData(float $latitude, float $longitude): array
    {
        $cacheKey = $this->buildCacheKey($latitude, $longitude);

        return Cache::remember($cacheKey, self::CACHE_DURATION_SECONDS, function () use ($latitude, $longitude): array {
            $daily = $this->fetchDailyTemperatures($latitude, $longitude);
            $means = array_column($daily, 'mean');
            $average = $this->calculateAverage($means);

            return [
                'daily_temperatures' => $daily,
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
     * @return array<int, array{date: string, mean: float, min: float|null, max: float|null}>
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
                    'daily' => 'temperature_2m_mean,temperature_2m_min,temperature_2m_max',
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
        $daily = $data['daily'] ?? [];

        $means = $daily['temperature_2m_mean'] ?? null;
        $dates = $daily['time'] ?? [];
        $mins = $daily['temperature_2m_min'] ?? [];
        $maxs = $daily['temperature_2m_max'] ?? [];

        if (! is_array($means) || count($means) === 0) {
            throw new \RuntimeException('Weather service returned an unexpected payload.');
        }

        $result = [];
        foreach ($means as $i => $mean) {
            if ($mean === null) {
                continue;
            }

            $result[] = [
                'date' => $dates[$i] ?? '',
                'mean' => $mean,
                'min' => $mins[$i] ?? null,
                'max' => $maxs[$i] ?? null,
            ];
        }

        return array_reverse($result);
    }

    /**
     * Reverse-geocode coordinates into a human-readable location name.
     * Returns null on failure so callers can degrade gracefully.
     */
    public function reverseGeocode(float $latitude, float $longitude): ?string
    {
        $cacheKey = "geocode:{$this->roundCoord($latitude)}:{$this->roundCoord($longitude)}";

        return Cache::remember($cacheKey, self::CACHE_DURATION_SECONDS, function () use ($latitude, $longitude): ?string {
            try {
                $response = Http::timeout(5)
                    ->retry(2, 300, throw: false)
                    ->withHeaders(['User-Agent' => config('app.name', 'Laravel').' (season-detector)'])
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'format' => 'json',
                        'zoom' => 10,
                    ]);

                if ($response->failed()) {
                    return null;
                }

                $data = $response->json();
                $address = $data['address'] ?? [];

                $parts = array_filter([
                    $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null,
                    $address['state'] ?? $address['region'] ?? null,
                    $address['postcode'] ?? null,
                    $address['country'] ?? null,
                ]);

                return $parts !== [] ? implode(', ', $parts) : ($data['display_name'] ?? null);
            } catch (ConnectionException|RequestException $e) {
                return null;
            }
        });
    }

    private function buildCacheKey(float $latitude, float $longitude): string
    {
        return "weather:v2:{$this->roundCoord($latitude)}:{$this->roundCoord($longitude)}";
    }

    private function roundCoord(float $value): float
    {
        return round($value, 2);
    }
}
