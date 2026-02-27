<?php

use App\Livewire\SeasonDetector;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeDailyResponse(array $means, ?array $mins = null, ?array $maxs = null): array
{
    $count = count($means);
    $dates = collect(range(1, $count))
        ->map(fn ($i) => now()->subDays($count - $i + 1)->format('Y-m-d'))
        ->all();

    return [
        'daily' => [
            'time' => $dates,
            'temperature_2m_mean' => $means,
            'temperature_2m_min' => $mins ?? array_map(fn ($m) => $m !== null ? $m - 3 : null, $means),
            'temperature_2m_max' => $maxs ?? array_map(fn ($m) => $m !== null ? $m + 3 : null, $means),
        ],
    ];
}

test('season detector page loads successfully', function () {
    $this->get('/')->assertOk();
});

test('checkSeason sets Spring for warm temperatures', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response(fakeDailyResponse(array_fill(0, 14, 12.0))),
        'nominatim.openstreetmap.org/*' => Http::response(['display_name' => 'Vienna']),
    ]);

    Livewire::test(SeasonDetector::class)
        ->call('checkSeason', 48.21, 16.37)
        ->assertSet('season', 'Spring!')
        ->assertSet('averageTemperature', 12.0)
        ->assertSet('loading', false);
});

test('checkSeason sets Winter for cold temperatures', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response(fakeDailyResponse(array_fill(0, 14, 3.0))),
        'nominatim.openstreetmap.org/*' => Http::response(['display_name' => 'Vienna']),
    ]);

    Livewire::test(SeasonDetector::class)
        ->call('checkSeason', 48.21, 16.37)
        ->assertSet('season', 'Winter!')
        ->assertSet('averageTemperature', 3.0)
        ->assertSet('loading', false);
});

test('checkSeason handles API errors gracefully', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response('Error', 500),
    ]);

    Livewire::test(SeasonDetector::class)
        ->call('checkSeason', 48.21, 16.37)
        ->assertSet('season', null)
        ->assertNotSet('error', null)
        ->assertSet('loading', false);
});

test('setError sets error message and stops loading', function () {
    Livewire::test(SeasonDetector::class)
        ->call('setError', 'Geolocation denied')
        ->assertSet('error', 'Geolocation denied')
        ->assertSet('loading', false);
});

test('POST /season/check returns season data', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response(fakeDailyResponse(array_fill(0, 14, 10.0))),
    ]);

    $response = $this->postJson('/season/check', [
        'latitude' => 48.21,
        'longitude' => 16.37,
    ]);

    $response->assertOk()
        ->assertJsonPath('season', 'Spring!')
        ->assertJsonStructure(['daily_temperatures', 'average_temperature', 'season']);

    expect($response->json('average_temperature'))->toBeNumeric();
});

test('POST /season/check validates coordinates', function () {
    $this->postJson('/season/check', [
        'latitude' => 'invalid',
        'longitude' => 200,
    ])->assertUnprocessable();
});

test('getTemperatureData fetches and computes average from Open-Meteo', function () {
    $means = [2.0, 4.0, 6.0, 8.0, 10.0, 12.0, 14.0, 3.0, 5.0, 7.0, 9.0, 11.0, 13.0, 15.0];

    Http::fake([
        'api.open-meteo.com/*' => Http::response(fakeDailyResponse($means)),
    ]);

    $data = app(\App\Services\WeatherService::class)->getTemperatureData(48.21, 16.37);

    expect($data)
        ->toHaveKeys(['daily_temperatures', 'average_temperature'])
        ->and($data['daily_temperatures'])->toHaveCount(14)
        ->and($data['daily_temperatures'][0])->toHaveKeys(['date', 'mean', 'min', 'max'])
        ->and($data['average_temperature'])->toBe(8.5);
});

test('getTemperatureData throws on API failure', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response('Server Error', 500),
    ]);

    app(\App\Services\WeatherService::class)->getTemperatureData(48.21, 16.37);
})->throws(\RuntimeException::class);

test('getTemperatureData throws on malformed response', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response(['daily' => []]),
    ]);

    app(\App\Services\WeatherService::class)->getTemperatureData(48.21, 16.37);
})->throws(\RuntimeException::class, 'unexpected payload');

test('getTemperatureData filters out null mean values', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response(fakeDailyResponse([5.0, null, 10.0, null, 15.0])),
    ]);

    $data = app(\App\Services\WeatherService::class)->getTemperatureData(48.21, 16.37);

    expect($data['daily_temperatures'])->toHaveCount(3)
        ->and($data['daily_temperatures'][0]['mean'])->toEqual(15.0)
        ->and($data['daily_temperatures'][1]['mean'])->toEqual(10.0)
        ->and($data['daily_temperatures'][2]['mean'])->toEqual(5.0)
        ->and($data['average_temperature'])->toEqual(10.0);
});
