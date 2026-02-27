<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function check(Request $request, WeatherService $weatherService): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $data = $weatherService->getTemperatureData(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
            );

            return response()->json([
                'daily_temperatures' => $data['daily_temperatures'],
                'average_temperature' => $data['average_temperature'],
                'season' => $weatherService->determineSeason($data['average_temperature']),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }
}
