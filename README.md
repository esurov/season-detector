# Season Detector

A Laravel application that determines whether it is currently **Spring** or **Winter** at your location, based on real temperature data from the past 14 days.

The app requests your browser's geolocation, fetches recent daily temperatures from the Open-Meteo weather API, computes the 14-day average, and displays the result with a simple rule: above 7 °C means Spring, otherwise Winter.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Livewire 4 + Blaze
- Tailwind CSS 4
- Pest 3 (testing)
- SQLite (default database/cache)

## Installation

```bash
# Clone and enter the project
cd weather

# Install dependencies and build assets
composer run setup

# Start the development server
composer run dev
```

The application will be available at `http://localhost:8000`.

No API keys are required. The Open-Meteo API and Nominatim geocoding service are both free and keyless.

### Environment

The only weather-specific configuration is the Open-Meteo base URL, which can be overridden in `.env`:

```
OPEN_METEO_BASE_URL=https://api.open-meteo.com
```

## Weather Provider

The app uses the [Open-Meteo API](https://open-meteo.com/) — a free, open-source weather API that requires no authentication.

### API Request

A single request is made to the forecast endpoint:

```
GET https://api.open-meteo.com/v1/forecast
```

Parameters:

| Parameter             | Value                                      |
|-----------------------|--------------------------------------------|
| `latitude`            | From browser geolocation                   |
| `longitude`           | From browser geolocation                   |
| `daily`               | `temperature_2m_mean,temperature_2m_min,temperature_2m_max` |
| `start_date`          | 14 days ago (YYYY-MM-DD)                   |
| `end_date`            | Yesterday (YYYY-MM-DD)                     |
| `timezone`            | `auto`                                     |

All temperatures are returned in Celsius.

### Resilience

- **Timeout**: 10 seconds per request
- **Retries**: Up to 3 attempts with 500 ms delay between retries
- **Caching**: Results are cached for 1 hour, keyed by coordinates rounded to 2 decimal places (~1.1 km precision)
- **Error handling**: Connection failures, HTTP errors, and malformed payloads are caught and surfaced as user-friendly messages

### Reverse Geocoding

Location names (city, region, postcode, country) are resolved via the [Nominatim](https://nominatim.openstreetmap.org/) reverse geocoding API. This is a best-effort lookup — if it fails, only coordinates are shown.

## Calculation Algorithm

1. The browser sends the user's latitude and longitude to the server via a Livewire action.
2. `WeatherService` fetches the **daily mean temperature** (`temperature_2m_mean`) for each of the last 14 days.
3. Null values (missing data points) are filtered out.
4. The **arithmetic mean** of the remaining daily temperatures is calculated:

```
average = sum(daily_means) / count(daily_means)
```

5. The season is determined by a single threshold:

| Condition               | Result      |
|-------------------------|-------------|
| average > 7.0 °C       | **Spring!** |
| average ≤ 7.0 °C       | **Winter!** |

The threshold of 7 °C is a simplified proxy: in many temperate climates, sustained averages above this value correlate with the onset of spring growing conditions.

## Project Structure

```
app/
├── Http/Controllers/
│   └── SeasonController.php      # POST /season/check JSON endpoint
├── Livewire/
│   └── SeasonDetector.php        # Main Livewire component
└── Services/
    └── WeatherService.php        # API client, caching, calculation, season logic

resources/views/
├── components/layouts/
│   └── app.blade.php             # App layout
└── livewire/
    └── season-detector.blade.php # Component view with Alpine.js geolocation

routes/
└── web.php                       # GET / (Livewire) + POST /season/check

config/
└── services.php                  # open_meteo.base_url configuration

tests/
├── Feature/
│   └── SeasonDetectorTest.php    # Livewire, controller, and service integration tests
└── Unit/
    └── WeatherServiceTest.php    # Average calculation and season determination
```

## Testing

```bash
php artisan test
```

Tests cover:

- Season determination logic (Spring/Winter threshold)
- Average temperature calculation (including empty arrays)
- API response parsing (success, failure, malformed, null values)
- Livewire component state transitions
- Controller validation and JSON responses
