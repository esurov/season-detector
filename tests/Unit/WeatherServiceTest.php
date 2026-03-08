<?php

use App\Services\WeatherService;

beforeEach(function () {
    $this->service = new WeatherService;
});

test('calculateAverage returns correct mean', function () {
    expect($this->service->calculateAverage([5.0, 10.0, 15.0, 20.0]))->toBe(12.5);
});

test('calculateAverage returns zero for empty array', function () {
    expect($this->service->calculateAverage([]))->toBe(0.0);
});

test('determineSeason returns Spring when all days above threshold', function () {
    expect($this->service->determineSeason(array_fill(0, 14, 7.1)))->toBe('Spring!')
        ->and($this->service->determineSeason(array_fill(0, 14, 15.0)))->toBe('Spring!');
});

test('determineSeason returns Winter when any day is at or below threshold', function () {
    expect($this->service->determineSeason(array_fill(0, 14, 7.0)))->toBe('Winter!')
        ->and($this->service->determineSeason(array_fill(0, 14, 3.0)))->toBe('Winter!')
        ->and($this->service->determineSeason([...array_fill(0, 13, 12.0), 5.0]))->toBe('Winter!');
});
