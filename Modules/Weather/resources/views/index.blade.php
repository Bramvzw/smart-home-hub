<x-dashboard.layout title="Weather" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Weather/resources/assets/css/weather.css'])
    </x-slot:head>

    <x-slot:scripts>
        @vite(['Modules/Weather/resources/assets/js/weather.js'])
    </x-slot:scripts>

    @php
        $conditionIcon = static function (string $condition, string $class = 'weather-glyph'): string {
            $lower = strtolower($condition);
            $inner = match (true) {
                str_contains($lower, 'clear') =>
                    '<circle cx="12" cy="12" r="4.2"/><path d="M12 3v2.4M12 18.6V21M3 12h2.4M18.6 12H21M5.6 5.6l1.7 1.7M16.7 16.7l1.7 1.7M18.4 5.6l-1.7 1.7M7.3 16.7l-1.7 1.7"/>',
                str_contains($lower, 'partly') =>
                    '<path d="M8 5.5V4M4.5 7l-1-1M12 8.5l1-1M5.5 11.5H4"/><circle cx="8" cy="9" r="2.6"/><path d="M9 19a4 4 0 01-.4-7.98A5.3 5.3 0 0118.7 12 3.2 3.2 0 0118 19z"/>',
                str_contains($lower, 'rain') || str_contains($lower, 'shower') || str_contains($lower, 'drizzle') || str_contains($lower, 'thunder') =>
                    '<path d="M7.5 14.5a4.3 4.3 0 01-.5-8.57A5.7 5.7 0 0118.3 7.1 3.5 3.5 0 0118 14H8"/><path d="M8.5 17l-1 2.5M12.5 17l-1 2.5M16.5 17l-1 2.5"/>',
                default =>
                    '<path d="M7.5 18a4.5 4.5 0 01-.5-8.97A6 6 0 0118.8 10.3 3.6 3.6 0 0118 18z"/>',
            };
            return '<svg class="'.e($class).'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
        };

        $rainExpected = count($rainyBlocks) > 0;
        $windExpected = count($windyBlocks) > 0;
        $currentProbability = $windowBlocks[0]->precipitationProbability ?? null;
        $refreshMinutes = max(1, (int) round($refreshSeconds / 60));
        $statusTitle = $rainEvent ? 'Rain in '.$rainEvent['minutes_until'].' min' : 'Staying dry';
        $statusDetail = $rainEvent
            ? 'Starts at '.$rainEvent['first']->time->format('H:i').', likely '.$rainEvent['duration_hours'].' hour'.($rainEvent['duration_hours'] === 1 ? '' : 's').'. '.$rainEvent['intensity'].', total '.number_format($rainEvent['total_mm'], 1, '.', '').' mm in wet blocks.'
            : 'No hourly block in the next '.$windowHours.' hours crosses the alert threshold: more than '.number_format($precipitationThresholdMm, 1, '.', '').' mm or at least '.$probabilityThreshold.'% probability.';
        $statusPill = $rainEvent ? 'Alert active · starts '.$rainEvent['first']->time->format('H:i') : 'No rain alert needed';
        $statusTone = $rainEvent ? 'rain' : 'dry';
        $notificationsLabel = $notificationsConfigured ? 'ntfy active' : 'ntfy not configured';
        $alertHoursLabel = sprintf('%02d:00-%02d:00', $alertStartHour, $alertEndHour);
        $lastAlertSentAt = isset($lastAlert['sent_at'])
            ? \Carbon\CarbonImmutable::parse($lastAlert['sent_at'])->setTimezone((string) config('weather.location.timezone', 'Europe/Amsterdam'))->format('d-m H:i')
            : null;
        $dayAfterTomorrow = $forecast?->days[2] ?? null;
        $dayCards = array_values(array_filter([
            ['label' => 'Today',    'day' => $today],
            ['label' => 'Tomorrow', 'day' => $tomorrow],
            ['label' => $dayAfterTomorrow?->date->format('l') ?? 'In 2 days', 'day' => $dayAfterTomorrow],
        ], fn ($item) => $item['day'] !== null));
    @endphp

    <div class="weather-dashboard" data-weather data-weather-refresh-seconds="{{ $refreshSeconds }}">
        <div data-weather-content>
        @unless($configured)
            <div class="weather-empty">
                <div class="weather-empty__icon">
                    <x-dashboard.icons.weather class="h-6 w-6" />
                </div>
                <h3>No weather location configured</h3>
                <p>Set <code>WEATHER_LATITUDE</code> and <code>WEATHER_LONGITUDE</code> to enable rain alerts.</p>
            </div>
        @elseif($failed)
            <div class="weather-empty weather-empty--error">
                <div class="weather-empty__icon">
                    <x-dashboard.icons.weather class="h-6 w-6" />
                </div>
                <h3>Weather data unavailable</h3>
                    <p>Open-Meteo could not be loaded right now. The scheduled check will try again later.</p>
                </div>
        @else
            <header class="weather-topbar">
                <div class="weather-location">
                    <x-dashboard.icons.map-pin class="weather-location__icon" />
                    <strong>{{ $locationLabel }}</strong>
                    <span>{{ number_format($latitude, 5, ',', '') }}, {{ number_format($longitude, 5, ',', '') }}</span>
                </div>
            </header>

            <section id="weather-now" class="weather-hero" data-rain="{{ $rainExpected ? 'true' : 'false' }}" data-wind="{{ $windExpected ? 'true' : 'false' }}">
                <article class="weather-now-card" aria-label="Current weather">
                    <div class="weather-now-card__head">
                        <span>Now</span>
                        <span>Updated {{ $forecast->fetchedAt->format('H:i') }}</span>
                    </div>
                    <div class="weather-now-card__temp">
                        <strong>{{ $forecast->currentTemperature !== null ? number_format($forecast->currentTemperature, 1, '.', '') : 'n/a' }}</strong>
                        @if($forecast->currentTemperature !== null)
                            <span>°C</span>
                        @endif
                    </div>
                    <div class="weather-now-card__condition">
                        {!! $conditionIcon($forecast->currentConditionLabel()) !!}
                        <span>{{ $forecast->currentConditionLabel() }}</span>
                    </div>
                    <dl class="weather-now-card__stats">
                        <div>
                            <dt>Precipitation</dt>
                            <dd>{{ number_format($forecast->currentPrecipitationMm, 1, '.', '') }} mm</dd>
                        </div>
                        <div>
                            <dt>Rain chance</dt>
                            <dd>{{ $currentProbability !== null ? $currentProbability.'%' : 'n/a' }}</dd>
                        </div>
                    </dl>
                    <div class="weather-now-card__location">
                        <x-dashboard.icons.map-pin class="weather-small-icon" />
                        <span>{{ $locationLabel }}</span>
                    </div>
                </article>

                <div class="weather-hero__main">
                    <div class="weather-eyebrow">
                        <svg class="weather-small-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 9a6 6 0 0112 0c0 5 2 6 2 6H4s2-1 2-6z"/>
                            <path d="M9.5 19a2.5 2.5 0 005 0"/>
                        </svg>
                        <span>Alert window</span>
                    </div>
                    <h2>{{ $statusTitle }}</h2>
                    <p>{{ $statusDetail }}</p>
                    <div class="weather-status-row">
                        <span class="weather-pill" data-tone="{{ $statusTone }}">
                            <span></span>
                            {{ $statusPill }}
                        </span>
                        @if($windEvent)
                            <span class="weather-pill" data-tone="wind">
                                <span></span>
                                Wind from {{ $windEvent['first']->time->format('H:i') }}
                            </span>
                        @endif
                    </div>
                    <div class="weather-meta">
                        <div class="weather-meta-item">
                            <svg class="weather-meta-icon" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 9a6 6 0 0112 0c0 5 2 6 2 6H4s2-1 2-6z"/>
                                <path d="M9.5 19a2.5 2.5 0 005 0"/>
                            </svg>
                            <div>
                                <div class="weather-meta-key">Notification</div>
                                <div class="weather-meta-val">{{ $notificationsLabel }}</div>
                            </div>
                        </div>
                        <div class="weather-meta-item">
                            <svg class="weather-meta-icon" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 7h9M17 7h3"/>
                                <circle cx="15" cy="7" r="2"/>
                                <path d="M4 17h3M11 17h9"/>
                                <circle cx="9" cy="17" r="2"/>
                            </svg>
                            <div>
                                <div class="weather-meta-key">Trigger</div>
                                <div class="weather-meta-val">&gt; {{ number_format($precipitationThresholdMm, 1, '.', '') }} mm or {{ $probabilityThreshold }}%</div>
                            </div>
                        </div>
                        <div class="weather-meta-item">
                            <svg class="weather-meta-icon" width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.5"/>
                                <path d="M12 7.5V12l3.5 2"/>
                            </svg>
                            <div>
                                <div class="weather-meta-key">Alert hours</div>
                                <div class="weather-meta-val">{{ $alertHoursLabel }}</div>
                            </div>
                        </div>
                    </div>
                    @if($windEvent)
                        <div class="weather-inline-alert">
                            Wind from {{ $windEvent['first']->time->format('H:i') }}: max {{ number_format($windEvent['max_wind'], 0, '.', '') }} km/h, gusts {{ number_format($windEvent['max_gusts'], 0, '.', '') }} km/h.
                        </div>
                    @endif
                </div>
            </section>


            @if(count($dayCards))
            <section class="weather-days-strip" aria-label="Daily forecast">
                <div class="weather-section-head">
                    <h3>Days ahead</h3>
                    <span>Next {{ count($dayCards) }} days</span>
                </div>
                <div class="weather-day-cards">
                    @foreach($dayCards as $item)
                        @php
                            $day = $item['day'];
                            $dayRainy = ($day->precipitationProbabilityMax ?? 0) >= $probabilityThreshold
                                || $day->precipitationSumMm > $precipitationThresholdMm;
                            $dayWindy = ($day->windSpeedMaxKmh ?? 0) >= $windThresholdKmh;
                        @endphp
                        <article class="weather-day-card"
                                 data-rain="{{ $dayRainy ? 'true' : 'false' }}"
                                 data-wind="{{ $dayWindy ? 'true' : 'false' }}">
                            <div class="weather-day-card__top">
                                <div>
                                    <span class="weather-day-card__label">{{ $item['label'] }}</span>
                                    <div class="weather-day-card__date">{{ $day->date->format('l, j M') }}</div>
                                </div>
                                {!! $conditionIcon($day->conditionLabel()) !!}
                            </div>
                            <div class="weather-day-card__condition">{{ $day->conditionLabel() }}</div>
                            <div class="weather-day-card__temp">
                                <span>{{ $day->temperatureMin !== null ? number_format($day->temperatureMin, 0) : '?' }}</span>
                                <span class="sep">–</span>
                                <span>{{ $day->temperatureMax !== null ? number_format($day->temperatureMax, 0) : '?' }}</span>
                                <span class="unit">°C</span>
                            </div>
                            <div class="weather-day-card__metrics">
                                <span class="weather-day-card__mm">{{ number_format($day->precipitationSumMm, 1, '.', '') }} mm</span>
                                <span class="weather-day-card__pct">{{ $day->precipitationProbabilityMax ?? 0 }}%</span>
                            </div>
                            <div class="weather-day-card__bar" aria-hidden="true">
                                <i style="width: {{ max(2, min(100, $day->precipitationProbabilityMax ?? 0)) }}%"></i>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
            @endif

            <section id="weather-hours" class="weather-timeline" aria-label="Forecast blocks">
                <div class="weather-section-head">
                    <h3>Coming hours</h3>
                    <span>Fixed hourly blocks · {{ $windowHours }} hours ahead · Live refresh {{ $refreshMinutes }} min</span>
                </div>

                <div class="weather-hours">
                    @foreach($windowBlocks as $hour)
                        @php
                            $isRainy = $hour->isRainy($precipitationThresholdMm, $probabilityThreshold);
                            $isWindy = $hour->isWindy($windThresholdKmh);
                        @endphp
                        <article class="weather-hour" data-rain="{{ $isRainy ? 'true' : 'false' }}" data-wind="{{ $isWindy ? 'true' : 'false' }}">
                            <div class="weather-hour__top">
                                <time>{{ $hour->time->format('H:i') }}</time>
                                {!! $conditionIcon($hour->conditionLabel()) !!}
                            </div>
                            <strong>{{ $hour->conditionLabel() }}</strong>
                            <div class="weather-hour__metrics">
                                <span>{{ number_format($hour->precipitationMm, 1, '.', '') }} mm</span>
                                <span>{{ $hour->precipitationProbability ?? 0 }}%</span>
                            </div>
                            <div class="weather-hour__bar" aria-hidden="true">
                                <i style="width: {{ max(2, min(100, $hour->precipitationProbability ?? 0)) }}%"></i>
                            </div>
                            <dl>
                                <div>
                                    <dt>Intensity</dt>
                                    <dd>{{ $hour->rainIntensityLabel() }}</dd>
                                </div>
                                <div>
                                    <dt>Wind</dt>
                                    <dd>{{ $hour->windSpeedKmh !== null ? number_format($hour->windSpeedKmh, 0, '.', '').' km/h' : 'n/a' }}</dd>
                                </div>
                                <div>
                                    <dt>Temp</dt>
                                    <dd>{{ $hour->temperature !== null ? number_format($hour->temperature, 1, '.', '').'°C' : 'n/a' }}</dd>
                                </div>
                            </dl>
                        </article>
                    @endforeach
                </div>
            </section>

            @if(isset($lastAlert['message']) && is_string($lastAlert['message']))
                <section id="weather-alerts" class="weather-last-message" aria-label="Last rain message">
                    <div class="weather-section-head">
                        <h3>Last message</h3>
                        <span>{{ $lastAlertSentAt }}</span>
                    </div>
                    <pre>{{ $lastAlert['message'] }}</pre>
                </section>
            @endif
        @endunless
        </div>
    </div>
</x-dashboard.layout>
