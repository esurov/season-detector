<?php

namespace App\Livewire;

use App\Services\WeatherService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SeasonDetector extends Component
{
    public ?float $latitude = null;

    public ?float $longitude = null;

    /** @var float[] */
    public array $dailyTemperatures = [];

    public ?float $averageTemperature = null;

    public ?string $season = null;

    public bool $loading = false;

    public ?string $error = null;

    public function checkSeason(float $latitude, float $longitude, WeatherService $weatherService): void
    {
        $this->reset(['error', 'season', 'dailyTemperatures', 'averageTemperature']);

        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->loading = true;

        try {
            $data = $weatherService->getTemperatureData($latitude, $longitude);

            $this->dailyTemperatures = $data['daily_temperatures'];
            $this->averageTemperature = $data['average_temperature'];
            $this->season = $weatherService->determineSeason($data['average_temperature']);
        } catch (\RuntimeException $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function setError(string $message): void
    {
        $this->error = $message;
        $this->loading = false;
    }
}
