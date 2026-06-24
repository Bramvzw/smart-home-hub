<x-dashboard.layout title="Weather" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Weather/resources/assets/css/weather.css'])
    </x-slot:head>

    <x-slot:scripts>
        @vite(['Modules/Weather/resources/assets/js/weather.js'])
    </x-slot:scripts>

    @php
        $wxIcon = static function (string $key, int $size = 20): string {
            $inner = match ($key) {
                'pin' => '<path d="M12 21s7-5.6 7-11a7 7 0 10-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
                'bell' => '<path d="M6 9a6 6 0 0112 0c0 5 2 6 2 6H4s2-1 2-6z"/><path d="M9.5 19a2.5 2.5 0 005 0"/>',
                'trigger' => '<path d="M4 7h9M17 7h3"/><circle cx="15" cy="7" r="2"/><path d="M4 17h3M11 17h9"/><circle cx="9" cy="17" r="2"/>',
                'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3.5 2"/>',
                'clear' => '<circle cx="12" cy="12" r="4.1"/><path d="M12 3v2.4M12 18.6V21M3 12h2.4M18.6 12H21M5.6 5.6l1.7 1.7M16.7 16.7l1.7 1.7M18.4 5.6l-1.7 1.7M7.3 16.7l-1.7 1.7"/>',
                'rain' => '<path d="M7.5 14.5a4.3 4.3 0 01-.5-8.57A5.7 5.7 0 0118.3 7.1 3.5 3.5 0 0118 14H8"/><path d="M8.5 17l-1 2.5M12.5 17l-1 2.5M16.5 17l-1 2.5"/>',
                default => '<path d="M8 5.5V4M4.5 7l-1-1M12 8.5l1-1M5.5 11.5H4"/><circle cx="8" cy="9" r="2.5"/><path d="M9 19a4 4 0 01-.4-7.98A5.3 5.3 0 0118.7 12 3.2 3.2 0 0118 19z"/>',
            };

            return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
        };

        $conditionKey = static function (string $condition): string {
            $lower = strtolower($condition);

            return match (true) {
                str_contains($lower, 'clear') => 'clear',
                str_contains($lower, 'partly') => 'partly',
                str_contains($lower, 'rain') || str_contains($lower, 'shower')
                    || str_contains($lower, 'drizzle') || str_contains($lower, 'thunder') => 'rain',
                default => 'partly',
            };
        };

        // lo/hi temps are mapped onto a shared scale for the min–max track
        $tempMin = 14;
        $tempMax = 40;
        $tempPos = static function (?float $t) use ($tempMin, $tempMax): float {
            if ($t === null) {
                return 0.0;
            }

            return max(0.0, min(100.0, (($t - $tempMin) / ($tempMax - $tempMin)) * 100));
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
        $alertHoursLabel = sprintf('%02d:00–%02d:00', $alertStartHour, $alertEndHour);
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

    <div class="weather-page" data-weather data-weather-refresh-seconds="{{ $refreshSeconds }}">
        <div data-weather-content>
        @unless($configured)
            <div class="wxp-empty">
                <div class="wxp-empty__icon">
                    <x-dashboard.icons.weather class="h-6 w-6" />
                </div>
                <h3>No weather location configured</h3>
                <p>Set <code>WEATHER_LATITUDE</code> and <code>WEATHER_LONGITUDE</code> to enable rain alerts.</p>
            </div>
        @elseif($failed)
            <div class="wxp-empty wxp-empty--error">
                <div class="wxp-empty__icon">
                    <x-dashboard.icons.weather class="h-6 w-6" />
                </div>
                <h3>Weather data unavailable</h3>
                <p>Open-Meteo could not be loaded right now. The scheduled check will try again later.</p>
            </div>
        @else
            <div class="wxp">
                <div class="wxp-loc">
                    <span class="ic">{!! $wxIcon('pin', 17) !!}</span>
                    <b>{{ $locationLabel }}</b>
                    <span class="coords">{{ number_format($latitude, 5, ',', '') }}, {{ number_format($longitude, 5, ',', '') }}</span>
                </div>

                <div class="wxp-top">
                    <div class="wxp-now" aria-label="Current weather">
                        <div class="wxp-now-head">
                            <span class="lbl">Now</span>
                            <span class="upd">Updated {{ $forecast->fetchedAt->format('H:i') }}</span>
                        </div>
                        <div class="wxp-now-temp">
                            <span class="deg">{{ $forecast->currentTemperature !== null ? number_format($forecast->currentTemperature, 1, '.', '') : 'n/a' }}@if($forecast->currentTemperature !== null)°@endif</span>
                            @if($forecast->currentTemperature !== null)
                                <span class="u">C</span>
                            @endif
                        </div>
                        <div class="wxp-now-cond">
                            <span class="ic">{!! $wxIcon($conditionKey($forecast->currentConditionLabel()), 22) !!}</span>
                            {{ $forecast->currentConditionLabel() }}
                        </div>
                        <div class="wxp-now-stats">
                            <div class="wxp-now-stat">
                                <div class="k">Precipitation</div>
                                <div class="v">{{ number_format($forecast->currentPrecipitationMm, 1, '.', '') }} mm</div>
                            </div>
                            <div class="wxp-now-stat">
                                <div class="k">Rain chance</div>
                                <div class="v">{{ $currentProbability !== null ? $currentProbability.'%' : 'n/a' }}</div>
                            </div>
                        </div>
                        <div class="wxp-now-foot">
                            <span class="ic">{!! $wxIcon('pin', 15) !!}</span>
                            {{ $locationLabel }}
                        </div>
                    </div>

                    <div class="wxp-alert">
                        <span class="wxp-eyebrow">{!! $wxIcon('bell', 15) !!} Alert window</span>
                        <h1 class="wxp-alert-title">{{ $statusTitle }}</h1>
                        <p class="wxp-alert-sub">{{ $statusDetail }}</p>
                        <div>
                            <span class="wxp-pill" data-tone="{{ $statusTone }}"><span class="dot"></span> {{ $statusPill }}</span>
                            @if($windEvent)
                                <span class="wxp-pill" data-tone="rain"><span class="dot"></span> Wind from {{ $windEvent['first']->time->format('H:i') }}</span>
                            @endif
                        </div>
                        <div class="wxp-config">
                            <div class="wxp-cfg">
                                <span class="ic">{!! $wxIcon('bell', 19) !!}</span>
                                <div>
                                    <div class="k">Notification</div>
                                    <div class="v">{{ $notificationsLabel }}</div>
                                </div>
                            </div>
                            <div class="wxp-cfg">
                                <span class="ic">{!! $wxIcon('trigger', 19) !!}</span>
                                <div>
                                    <div class="k">Trigger</div>
                                    <div class="v">&gt; {{ number_format($precipitationThresholdMm, 1, '.', '') }} mm or {{ $probabilityThreshold }}%</div>
                                </div>
                            </div>
                            <div class="wxp-cfg">
                                <span class="ic">{!! $wxIcon('clock', 19) !!}</span>
                                <div>
                                    <div class="k">Alert hours</div>
                                    <div class="v">{{ $alertHoursLabel }}</div>
                                </div>
                            </div>
                        </div>
                        @if($windEvent)
                            <div class="wxp-inline-alert">
                                Wind from {{ $windEvent['first']->time->format('H:i') }}: max {{ number_format($windEvent['max_wind'], 0, '.', '') }} km/h, gusts {{ number_format($windEvent['max_gusts'], 0, '.', '') }} km/h.
                            </div>
                        @endif
                    </div>
                </div>

                <hr class="wxp-divide" />

                @if(count($dayCards))
                <div class="wxp-sec">
                    <span class="ttl">Days ahead</span>
                    <span class="meta">Next {{ count($dayCards) }} days</span>
                </div>
                <div class="wxp-days">
                    @foreach($dayCards as $item)
                        @php
                            $day = $item['day'];
                            $dayRainy = ($day->precipitationProbabilityMax ?? 0) >= $probabilityThreshold
                                || $day->precipitationSumMm > $precipitationThresholdMm;
                            $lo = $tempPos($day->temperatureMin);
                            $hi = $tempPos($day->temperatureMax);
                        @endphp
                        <div class="wxp-day{{ $dayRainy ? ' active rain' : '' }}">
                            <div class="wxp-day-head">
                                <div class="wxp-day-when">
                                    <div class="d">{{ $item['label'] }}</div>
                                    <div class="dt">{{ $day->date->format('l, j M') }}</div>
                                </div>
                                <span class="wic">{!! $wxIcon($conditionKey($day->conditionLabel()), 26) !!}</span>
                            </div>
                            <div class="wxp-day-cond">{{ $day->conditionLabel() }}</div>
                            <div class="wxp-day-temp">
                                <span class="lo">{{ $day->temperatureMin !== null ? number_format($day->temperatureMin, 0) : '?' }}</span>
                                <span class="sep">–</span>
                                <span class="hi">{{ $day->temperatureMax !== null ? number_format($day->temperatureMax, 0) : '?' }}</span>
                                <span class="u">°C</span>
                            </div>
                            <div class="wxp-temp-track"><i style="left: {{ $lo }}%; width: {{ max(0, $hi - $lo) }}%"></i></div>
                            <div class="wxp-day-precip">
                                <span class="mm">{{ number_format($day->precipitationSumMm, 1, '.', '') }} mm</span>
                                <span class="pct">{{ $day->precipitationProbabilityMax ?? 0 }}%</span>
                            </div>
                            <div class="wxp-precip-bar"><i style="width: {{ max(1.5, min(100, $day->precipitationProbabilityMax ?? 0)) }}%"></i></div>
                        </div>
                    @endforeach
                </div>
                @endif

                <div class="wxp-sec" style="margin-top: 34px;">
                    <span class="ttl">Coming hours</span>
                    <span class="meta">Fixed hourly blocks · {{ $windowHours }} hours ahead · Live refresh {{ $refreshMinutes }} min</span>
                </div>
                <div class="wxp-hours">
                    @foreach($windowBlocks as $hour)
                        @php
                            $isRainy = $hour->isRainy($precipitationThresholdMm, $probabilityThreshold);
                            $intensity = $hour->rainIntensityLabel();
                        @endphp
                        <div class="wxp-hour{{ $isRainy ? ' rain' : '' }}">
                            <div class="wxp-hour-head">
                                <span class="wxp-hour-time">{{ $hour->time->format('H:i') }}</span>
                                <span class="wic">{!! $wxIcon($conditionKey($hour->conditionLabel()), 22) !!}</span>
                            </div>
                            <div class="wxp-hour-cond">{{ $hour->conditionLabel() }}</div>
                            <div class="wxp-hour-precip">
                                <span class="mm">{{ number_format($hour->precipitationMm, 1, '.', '') }} mm</span>
                                <span class="pct">{{ $hour->precipitationProbability ?? 0 }}%</span>
                            </div>
                            <div class="wxp-hour-bar"><i style="width: {{ max(1.5, min(100, $hour->precipitationProbability ?? 0)) }}%"></i></div>
                            <div class="wxp-hour-rows">
                                <div class="wxp-hour-row"><span class="k">Intensity</span><span class="v{{ $intensity === 'Dry' ? ' dry' : '' }}">{{ $intensity }}</span></div>
                                <div class="wxp-hour-row"><span class="k">Wind</span><span class="v">{{ $hour->windSpeedKmh !== null ? number_format($hour->windSpeedKmh, 0, '.', '').' km/h' : 'n/a' }}</span></div>
                                <div class="wxp-hour-row"><span class="k">Temp</span><span class="v">{{ $hour->temperature !== null ? number_format($hour->temperature, 1, '.', '').'°C' : 'n/a' }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(isset($lastAlert['message']) && is_string($lastAlert['message']))
                    <div class="wxp-last">
                        <div class="wxp-sec">
                            <span class="ttl">Last message</span>
                            <span class="meta">{{ $lastAlertSentAt }}</span>
                        </div>
                        <pre>{{ $lastAlert['message'] }}</pre>
                    </div>
                @endif
            </div>
        @endunless
        </div>
    </div>
</x-dashboard.layout>
