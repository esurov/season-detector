<div
    x-data="{
        locating: false,
        requestLocation() {
            if (!navigator.geolocation) {
                $wire.setError('Your browser does not support geolocation.');
                return;
            }

            this.locating = true;
            $wire.set('season', null);
            $wire.set('loading', true);
            $wire.set('error', null);

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.locating = false;
                    $wire.checkSeason(position.coords.latitude, position.coords.longitude);
                },
                (error) => {
                    this.locating = false;
                    $wire.set('loading', false);

                    const messages = {
                        1: 'Location permission denied. Please allow location access and try again.',
                        2: 'Unable to determine your location. Please try again.',
                        3: 'Location request timed out. Please try again.',
                    };

                    $wire.setError(messages[error.code] || 'An unknown geolocation error occurred.');
                },
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
            );
        }
    }"
    x-init="requestLocation()"
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-sky-100 to-blue-200 dark:from-slate-900 dark:to-slate-800 p-6"
>
    <div class="w-full max-w-lg">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-8 space-y-6">
            <h1 class="text-3xl font-bold text-center text-slate-800 dark:text-slate-100">
                Season Detector
            </h1>

            <p class="text-center text-slate-500 dark:text-slate-400 text-sm">
                Detect whether it's Spring or Winter at your location based on the last 14 days of temperature data.
            </p>

            {{-- Detect Button (shown on error or as fallback) --}}
            @if ($error)
                <div class="flex justify-center">
                    <button
                        x-on:click="requestLocation()"
                        x-bind:disabled="locating"
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold rounded-xl transition-colors cursor-pointer"
                    >
                        Try Again
                    </button>
                </div>
            @endif

            {{-- Loading --}}
            @if ($loading)
                <div class="flex flex-col items-center space-y-3 py-4" wire:key="loading">
                    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-slate-500 dark:text-slate-400">Fetching weather data&hellip;</p>
                </div>
            @endif

            {{-- Error --}}
            @if ($error)
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl p-4" wire:key="error">
                    <p class="text-red-700 dark:text-red-400 text-sm text-center">{{ $error }}</p>
                </div>
            @endif

            {{-- Results --}}
            @if ($season)
                <div class="space-y-5" wire:key="results">
                    {{-- Season Result --}}
                    <div class="text-center py-8 rounded-xl {{ $season === 'Spring!' ? 'bg-green-50 dark:bg-green-900/30' : 'bg-blue-50 dark:bg-blue-900/30' }}">
                        @if ($season === 'Spring!')
                            {{-- Spring: sun with sprouting leaf --}}
                            <svg class="mx-auto mb-4 h-20 w-20" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                {{-- Sun rays --}}
                                <g stroke="#facc15" stroke-width="3" stroke-linecap="round">
                                    <line x1="50" y1="8" x2="50" y2="18" />
                                    <line x1="50" y1="62" x2="50" y2="72" />
                                    <line x1="18" y1="40" x2="28" y2="40" />
                                    <line x1="72" y1="40" x2="82" y2="40" />
                                    <line x1="26.3" y1="17.3" x2="33.4" y2="24.4" />
                                    <line x1="66.6" y1="55.6" x2="73.7" y2="62.7" />
                                    <line x1="73.7" y1="17.3" x2="66.6" y2="24.4" />
                                    <line x1="33.4" y1="55.6" x2="26.3" y2="62.7" />
                                </g>
                                {{-- Sun body --}}
                                <circle cx="50" cy="40" r="16" fill="#fbbf24" />
                                <circle cx="50" cy="40" r="16" fill="url(#sunGrad)" />
                                <defs>
                                    <radialGradient id="sunGrad" cx="0.4" cy="0.35" r="0.6">
                                        <stop offset="0%" stop-color="#fde68a" />
                                        <stop offset="100%" stop-color="#f59e0b" />
                                    </radialGradient>
                                </defs>
                                {{-- Stem --}}
                                <path d="M50 72 Q50 82 50 92" stroke="#16a34a" stroke-width="2.5" fill="none" stroke-linecap="round" />
                                {{-- Left leaf --}}
                                <path d="M50 84 Q40 78 36 70 Q44 74 50 84Z" fill="#22c55e" />
                                {{-- Right leaf --}}
                                <path d="M50 78 Q60 72 64 64 Q56 70 50 78Z" fill="#4ade80" />
                            </svg>
                        @else
                            {{-- Winter: snowflake --}}
                            <svg class="mx-auto mb-4 h-20 w-20" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g stroke="#60a5fa" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    {{-- Main axes --}}
                                    <line x1="50" y1="10" x2="50" y2="90" />
                                    <line x1="15.4" y1="30" x2="84.6" y2="70" />
                                    <line x1="15.4" y1="70" x2="84.6" y2="30" />
                                    {{-- Top branch --}}
                                    <line x1="50" y1="22" x2="42" y2="14" />
                                    <line x1="50" y1="22" x2="58" y2="14" />
                                    {{-- Bottom branch --}}
                                    <line x1="50" y1="78" x2="42" y2="86" />
                                    <line x1="50" y1="78" x2="58" y2="86" />
                                    {{-- Upper-right branch --}}
                                    <line x1="72.6" y1="37" x2="76" y2="27" />
                                    <line x1="72.6" y1="37" x2="82" y2="41" />
                                    {{-- Lower-left branch --}}
                                    <line x1="27.4" y1="63" x2="24" y2="73" />
                                    <line x1="27.4" y1="63" x2="18" y2="59" />
                                    {{-- Upper-left branch --}}
                                    <line x1="27.4" y1="37" x2="18" y2="41" />
                                    <line x1="27.4" y1="37" x2="24" y2="27" />
                                    {{-- Lower-right branch --}}
                                    <line x1="72.6" y1="63" x2="82" y2="59" />
                                    <line x1="72.6" y1="63" x2="76" y2="73" />
                                </g>
                                {{-- Center diamond --}}
                                <circle cx="50" cy="50" r="5" fill="#93c5fd" />
                            </svg>
                        @endif
                        <p class="text-6xl font-extrabold {{ $season === 'Spring!' ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400' }}">
                            {{ $season }}
                        </p>
                    </div>

                    {{-- Location --}}
                    <div class="text-center space-y-1">
                        @if ($locationName)
                            <p class="text-base font-medium text-slate-700 dark:text-slate-200">{{ $locationName }}</p>
                        @endif
                        <p class="text-sm text-slate-400 dark:text-slate-500">
                            {{ number_format($latitude, 4) }}, {{ number_format($longitude, 4) }}
                        </p>
                    </div>

                    {{-- Average Temperature --}}
                    <div class="text-center">
                        <span class="text-sm text-slate-500 dark:text-slate-400">14-day average:</span>
                        <span class="ml-1 text-lg font-semibold text-slate-800 dark:text-slate-100">
                            {{ number_format($averageTemperature, 1) }} &deg;C
                        </span>
                    </div>

                    {{-- Daily Temperatures --}}
                    <div>
                        <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Daily temperatures</h3>
                        <div class="grid grid-cols-7 gap-2">
                            @foreach ($dailyTemperatures as $index => $day)
                                <div
                                    wire:key="temp-{{ $index }}"
                                    x-data="{ open: false }"
                                    x-on:mouseenter="open = true"
                                    x-on:mouseleave="open = false"
                                    class="relative text-center py-2 px-1 rounded-lg text-sm font-mono cursor-default
                                        {{ $day['mean'] > 7 ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400' }}"
                                >
                                    {{ number_format($day['mean'], 1) }}&deg;

                                    {{-- Tooltip --}}
                                    <div
                                        x-show="open"
                                        x-cloak
                                        x-transition.opacity.duration.150ms
                                        class="absolute z-10 bottom-full left-1/2 -translate-x-1/2 mb-2 w-36 rounded-lg bg-slate-800 dark:bg-slate-700 text-white text-xs shadow-lg p-2.5 pointer-events-none"
                                    >
                                        <p class="font-semibold mb-1">{{ \Carbon\Carbon::parse($day['date'])->format('D, M j') }}</p>
                                        @if ($day['min'] !== null && $day['max'] !== null)
                                            <p>Min: {{ number_format($day['min'], 1) }} &deg;C</p>
                                            <p>Max: {{ number_format($day['max'], 1) }} &deg;C</p>
                                        @endif
                                        <p class="text-slate-300 mt-0.5">Mean: {{ number_format($day['mean'], 1) }} &deg;C</p>
                                        {{-- Arrow --}}
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-800 dark:border-t-slate-700"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Reset --}}
                    <div class="flex justify-center pt-2">
                        <button
                            x-on:click="requestLocation()"
                            class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 underline cursor-pointer"
                        >
                            Check again
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
