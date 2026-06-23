<?php

namespace Modules\Weather\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Weather\Data\RainAlertResult;
use Modules\Weather\Data\WeatherAlertResult;
use Modules\Weather\Data\WeatherDay;
use Modules\Weather\Data\WeatherForecast;
use Modules\Weather\Data\WeatherHour;

class WeatherService
{
    private const FORECAST_CACHE_KEY = 'weather:forecast';
    private const ACTIVE_RAIN_KEY = 'weather:rain-alert:active';
    private const COOLDOWN_KEY = 'weather:rain-alert:cooldown';
    private const WIND_ACTIVE_KEY = 'weather:wind-alert:active';
    private const WIND_COOLDOWN_KEY = 'weather:wind-alert:cooldown';
    private const DAILY_SUMMARY_KEY = 'weather:daily-summary';
    private const LAST_ALERT_KEY = 'weather:rain-alert:last';

    public function __construct(
        private readonly OpenMeteoClient $client,
        private readonly NtfyWeatherNotifier $notifier,
    ) {}

    public function isConfigured(): bool
    {
        return is_numeric(config('weather.location.latitude'))
            && is_numeric(config('weather.location.longitude'));
    }

    public function notificationsConfigured(): bool
    {
        return $this->notifier->isConfigured();
    }

    public function forecast(?CarbonImmutable $now = null): WeatherForecast
    {
        $now = $this->now($now);
        $latitude = (float) config('weather.location.latitude');
        $longitude = (float) config('weather.location.longitude');
        $timezone = (string) config('weather.location.timezone', 'Europe/Amsterdam');

        $data = Cache::remember(
            self::FORECAST_CACHE_KEY.':'.md5("{$latitude}:{$longitude}:{$timezone}:d3"),
            max(1, (int) config('weather.cache_ttl', 900)),
            fn (): array => $this->client->forecast($latitude, $longitude, $timezone),
        );

        return $this->mapForecast(is_array($data) ? $data : [], $now);
    }

    public function checkRainAlert(?CarbonImmutable $now = null): RainAlertResult
    {
        $now = $this->now($now);
        $forecast = $this->forecast($now);
        $rainyBlocks = $this->rainyBlocks($forecast, $now);

        if ($rainyBlocks === []) {
            Cache::forget(self::ACTIVE_RAIN_KEY);

            return new RainAlertResult($forecast, [], false, 'dry', 'No rain expected in the configured window.');
        }

        if (! $this->withinAlertHours($now)) {
            return new RainAlertResult($forecast, $rainyBlocks, false, 'outside_hours', 'Rain expected, but outside notification hours.');
        }

        if (! $this->notifier->isConfigured()) {
            return new RainAlertResult($forecast, $rainyBlocks, false, 'not_configured', 'Rain expected, but ntfy is not configured.');
        }

        if (Cache::has(self::ACTIVE_RAIN_KEY)) {
            return new RainAlertResult($forecast, $rainyBlocks, false, 'already_notified', 'This rain period has already been reported.');
        }

        if (Cache::has(self::COOLDOWN_KEY)) {
            return new RainAlertResult($forecast, $rainyBlocks, false, 'cooldown', 'Rain expected, but the cooldown is still active.');
        }

        $message = $this->notificationMessage($forecast, $rainyBlocks, $now);
        $this->notifier->send('Rain expected in Wijhe', $message);

        Cache::forever(self::ACTIVE_RAIN_KEY, [
            'first_rain_at' => $rainyBlocks[0]->time->toIso8601String(),
            'sent_at' => $now->toIso8601String(),
        ]);
        Cache::put(self::COOLDOWN_KEY, true, max(1, (int) config('weather.rain.cooldown_seconds', 3600)));
        Cache::forever(self::LAST_ALERT_KEY, [
            'sent_at' => $now->toIso8601String(),
            'first_rain_at' => $rainyBlocks[0]->time->toIso8601String(),
            'message' => $message,
        ]);

        return new RainAlertResult($forecast, $rainyBlocks, true, 'sent', $message);
    }

    public function checkWindAlert(?CarbonImmutable $now = null): WeatherAlertResult
    {
        $now = $this->now($now);
        $forecast = $this->forecast($now);
        $windyBlocks = $this->windyBlocks($forecast, $now);

        if ($windyBlocks === []) {
            Cache::forget(self::WIND_ACTIVE_KEY);

            return new WeatherAlertResult($forecast, [], false, 'calm', 'No strong wind expected in the configured window.');
        }

        if (! $this->withinAlertHours($now)) {
            return new WeatherAlertResult($forecast, $windyBlocks, false, 'outside_hours', 'Strong wind expected, but outside notification hours.');
        }

        if (! $this->notifier->isConfigured()) {
            return new WeatherAlertResult($forecast, $windyBlocks, false, 'not_configured', 'Strong wind expected, but ntfy is not configured.');
        }

        if (Cache::has(self::WIND_ACTIVE_KEY)) {
            return new WeatherAlertResult($forecast, $windyBlocks, false, 'already_notified', 'This wind period has already been reported.');
        }

        if (Cache::has(self::WIND_COOLDOWN_KEY)) {
            return new WeatherAlertResult($forecast, $windyBlocks, false, 'cooldown', 'Strong wind expected, but the cooldown is still active.');
        }

        $message = $this->windNotificationMessage($forecast, $windyBlocks, $now);
        $this->notifier->send('Strong wind expected in Wijhe', $message);

        Cache::forever(self::WIND_ACTIVE_KEY, [
            'first_wind_at' => $windyBlocks[0]->time->toIso8601String(),
            'sent_at' => $now->toIso8601String(),
        ]);
        Cache::put(self::WIND_COOLDOWN_KEY, true, max(1, (int) config('weather.wind.cooldown_seconds', 3600)));
        $this->rememberLastAlert($now, $windyBlocks[0]->time, $message, 'wind');

        return new WeatherAlertResult($forecast, $windyBlocks, true, 'sent', $message);
    }

    public function sendDailySummary(?CarbonImmutable $now = null): WeatherAlertResult
    {
        $now = $this->now($now);
        $forecast = $this->forecast($now);
        $today = $forecast->today();

        if (! (bool) config('weather.daily_summary.enabled', true)) {
            return new WeatherAlertResult($forecast, [], false, 'disabled', 'Daily weather summary is disabled.');
        }

        if (! $this->notifier->isConfigured()) {
            return new WeatherAlertResult($forecast, [], false, 'not_configured', 'Daily weather summary could not be sent because ntfy is not configured.');
        }

        if ($today === null) {
            return new WeatherAlertResult($forecast, [], false, 'missing_forecast', 'No daily forecast available.');
        }

        $key = self::DAILY_SUMMARY_KEY.':'.$now->toDateString();
        if (Cache::has($key)) {
            return new WeatherAlertResult($forecast, [], false, 'already_sent', 'The daily weather summary has already been sent today.');
        }

        $message = $this->dailySummaryMessage($forecast, $now);
        $this->notifier->send('Weather today in Wijhe', $message);

        Cache::put($key, true, $now->endOfDay());
        $this->rememberLastAlert($now, $now, $message, 'daily');

        return new WeatherAlertResult($forecast, [], true, 'sent', $message);
    }

    /**
     * @return list<WeatherHour>
     */
    public function rainyBlocks(WeatherForecast $forecast, ?CarbonImmutable $now = null): array
    {
        return $forecast->rainyBlocks(
            $this->now($now),
            (int) config('weather.rain.window_hours', 3),
            (float) config('weather.rain.precipitation_threshold_mm', 0),
            (int) config('weather.rain.probability_threshold', 50),
        );
    }

    /**
     * @return list<WeatherHour>
     */
    public function windyBlocks(WeatherForecast $forecast, ?CarbonImmutable $now = null): array
    {
        $threshold = (float) config('weather.wind.threshold_kmh', 50);

        return array_values(array_filter(
            $forecast->hourlyWindow($this->now($now), (int) config('weather.rain.window_hours', 3)),
            static fn (WeatherHour $hour): bool => $hour->isWindy($threshold),
        ));
    }

    /**
     * @return array{first: WeatherHour, duration_hours: int, minutes_until: int, total_mm: float, max_probability: int, intensity: string}|null
     */
    public function rainEvent(WeatherForecast $forecast, ?CarbonImmutable $now = null): ?array
    {
        $now = $this->now($now);
        $rainyBlocks = $this->rainyBlocks($forecast, $now);

        if ($rainyBlocks === []) {
            return null;
        }

        $first = $rainyBlocks[0];

        return [
            'first' => $first,
            'duration_hours' => $this->consecutiveRainDuration($forecast, $first),
            'minutes_until' => max(0, (int) $now->diffInMinutes($first->time, false)),
            'total_mm' => array_reduce($rainyBlocks, static fn (float $carry, WeatherHour $hour): float => $carry + $hour->precipitationMm, 0.0),
            'max_probability' => max(array_map(static fn (WeatherHour $hour): int => $hour->precipitationProbability ?? 0, $rainyBlocks)),
            'intensity' => $this->maxRainIntensity($rainyBlocks),
        ];
    }

    /**
     * @return array{first: WeatherHour, minutes_until: int, max_wind: float, max_gusts: float}|null
     */
    public function windEvent(WeatherForecast $forecast, ?CarbonImmutable $now = null): ?array
    {
        $now = $this->now($now);
        $windyBlocks = $this->windyBlocks($forecast, $now);

        if ($windyBlocks === []) {
            return null;
        }

        return [
            'first' => $windyBlocks[0],
            'minutes_until' => max(0, (int) $now->diffInMinutes($windyBlocks[0]->time, false)),
            'max_wind' => max(array_map(static fn (WeatherHour $hour): float => $hour->windSpeedKmh ?? 0.0, $windyBlocks)),
            'max_gusts' => max(array_map(static fn (WeatherHour $hour): float => $hour->windGustsKmh ?? 0.0, $windyBlocks)),
        ];
    }

    /**
     * @return array{sent_at?: string, first_rain_at?: string, message?: string}|null
     */
    public function lastAlert(): ?array
    {
        $last = Cache::get(self::LAST_ALERT_KEY);

        return is_array($last) ? $last : null;
    }

    public function withinAlertHours(?CarbonImmutable $now = null): bool
    {
        $now = $this->now($now);
        $start = max(0, min(23, (int) config('weather.rain.alert_start_hour', 7)));
        $end = max(0, min(24, (int) config('weather.rain.alert_end_hour', 23)));
        $hour = (int) $now->format('G');

        if ($start === $end) {
            return true;
        }

        if ($start < $end) {
            return $hour >= $start && $hour < $end;
        }

        return $hour >= $start || $hour < $end;
    }

    /**
     * @param  list<WeatherHour>  $rainyBlocks
     */
    public function notificationMessage(WeatherForecast $forecast, array $rainyBlocks, CarbonImmutable $now): string
    {
        $first = $rainyBlocks[0];
        $event = $this->rainEvent($forecast, $now);
        $totalMm = (float) ($event['total_mm'] ?? 0);
        $maxProbability = (int) ($event['max_probability'] ?? 0);
        $duration = (int) ($event['duration_hours'] ?? 1);
        $minutesUntil = (int) ($event['minutes_until'] ?? 0);
        $maxIntensity = (string) ($event['intensity'] ?? 'Dry');
        $lines = [
            "Location: {$forecast->locationLabel}",
            'Start: '.$first->time->format('H:i').' (in '.$minutesUntil.' min)',
            'Expected duration: '.$duration.' hour'.($duration === 1 ? '' : 's'),
            'Intensity: '.$maxIntensity,
            'Window: next '.((int) config('weather.rain.window_hours', 3)).' hours, fixed hourly blocks',
            'Total in wet blocks: '.number_format($totalMm, 1, '.', '').' mm',
            "Highest probability: {$maxProbability}%",
            'Thresholds: > '.number_format((float) config('weather.rain.precipitation_threshold_mm', 0), 1, '.', '').' mm or >= '.((int) config('weather.rain.probability_threshold', 50)).'%',
            'Generated: '.$now->setTimezone($forecast->timezone)->format('H:i'),
            '',
            'Blocks:',
        ];

        foreach ($rainyBlocks as $hour) {
            $lines[] = sprintf(
                '- %s: %s, %s mm, %s%% probability, %s',
                $hour->time->format('H:i'),
                $hour->conditionLabel(),
                number_format($hour->precipitationMm, 1, '.', ''),
                $hour->precipitationProbability ?? 0,
                $hour->rainIntensityLabel(),
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<WeatherHour>  $windyBlocks
     */
    public function windNotificationMessage(WeatherForecast $forecast, array $windyBlocks, CarbonImmutable $now): string
    {
        $first = $windyBlocks[0];
        $maxWind = max(array_map(static fn (WeatherHour $hour): float => $hour->windSpeedKmh ?? 0.0, $windyBlocks));
        $maxGusts = max(array_map(static fn (WeatherHour $hour): float => $hour->windGustsKmh ?? 0.0, $windyBlocks));
        $minutesUntil = max(0, $now->diffInMinutes($first->time, false));
        $lines = [
            "Location: {$forecast->locationLabel}",
            'First strong wind block: '.$first->time->format('H:i').' (in '.$minutesUntil.' min)',
            'Threshold: '.number_format((float) config('weather.wind.threshold_kmh', 50), 0, '.', '').' km/h',
            'Max wind: '.number_format($maxWind, 0, '.', '').' km/h',
            'Max gust: '.number_format($maxGusts, 0, '.', '').' km/h',
            'Generated: '.$now->setTimezone($forecast->timezone)->format('H:i'),
            '',
            'Blocks:',
        ];

        foreach ($windyBlocks as $hour) {
            $lines[] = sprintf(
                '- %s: wind %s km/h, gusts %s km/h',
                $hour->time->format('H:i'),
                number_format((float) $hour->windSpeedKmh, 0, '.', ''),
                number_format((float) $hour->windGustsKmh, 0, '.', ''),
            );
        }

        return implode("\n", $lines);
    }

    public function dailySummaryMessage(WeatherForecast $forecast, CarbonImmutable $now): string
    {
        $today = $forecast->today();
        $tomorrow = $forecast->tomorrow();
        $rainyBlocks = $this->rainyBlocks($forecast, $now);
        $windyBlocks = $this->windyBlocks($forecast, $now);
        $lines = [
            "Location: {$forecast->locationLabel}",
            'Today: '.$this->daySummaryLine($today),
            $tomorrow ? 'Tomorrow: '.$this->daySummaryLine($tomorrow) : null,
            $rainyBlocks !== [] ? 'First rain: '.$rainyBlocks[0]->time->format('H:i').' ('.$rainyBlocks[0]->rainIntensityLabel().')' : 'Rain: no trigger in the next '.((int) config('weather.rain.window_hours', 3)).' hours',
            $windyBlocks !== [] ? 'Wind alert: from '.$windyBlocks[0]->time->format('H:i') : 'Wind alert: no trigger in the next '.((int) config('weather.rain.window_hours', 3)).' hours',
            'Generated: '.$now->format('H:i'),
        ];

        return implode("\n", array_values(array_filter($lines)));
    }

    private function now(?CarbonImmutable $now): CarbonImmutable
    {
        return ($now ?? CarbonImmutable::now((string) config('weather.location.timezone', 'Europe/Amsterdam')))
            ->setTimezone((string) config('weather.location.timezone', 'Europe/Amsterdam'));
    }

    private function mapForecast(array $data, CarbonImmutable $now): WeatherForecast
    {
        $timezone = (string) config('weather.location.timezone', 'Europe/Amsterdam');
        $hourly = is_array($data['hourly'] ?? null) ? $data['hourly'] : [];
        $times = is_array($hourly['time'] ?? null) ? $hourly['time'] : [];
        $hours = [];

        foreach ($times as $index => $time) {
            $hours[] = new WeatherHour(
                time: CarbonImmutable::parse((string) $time, $timezone),
                temperature: $this->nullableFloat($hourly['temperature_2m'][$index] ?? null),
                precipitationMm: (float) ($hourly['precipitation'][$index] ?? 0),
                precipitationProbability: $this->nullableInt($hourly['precipitation_probability'][$index] ?? null),
                weatherCode: $this->nullableInt($hourly['weather_code'][$index] ?? null),
                windSpeedKmh: $this->nullableFloat($hourly['wind_speed_10m'][$index] ?? null),
                windGustsKmh: $this->nullableFloat($hourly['wind_gusts_10m'][$index] ?? null),
            );
        }

        $current = is_array($data['current'] ?? null) ? $data['current'] : [];
        $days = $this->mapDays(is_array($data['daily'] ?? null) ? $data['daily'] : [], $timezone);

        return new WeatherForecast(
            locationLabel: (string) config('weather.location.label', 'Wijhe'),
            latitude: (float) config('weather.location.latitude'),
            longitude: (float) config('weather.location.longitude'),
            timezone: $timezone,
            fetchedAt: $now,
            hours: $hours,
            days: $days,
            currentTemperature: $this->nullableFloat($current['temperature_2m'] ?? null),
            currentPrecipitationMm: (float) ($current['precipitation'] ?? 0),
            currentWeatherCode: $this->nullableInt($current['weather_code'] ?? null),
            currentWindSpeedKmh: $this->nullableFloat($current['wind_speed_10m'] ?? null),
            currentWindGustsKmh: $this->nullableFloat($current['wind_gusts_10m'] ?? null),
        );
    }

    /**
     * @return list<WeatherDay>
     */
    private function mapDays(array $daily, string $timezone): array
    {
        $times = is_array($daily['time'] ?? null) ? $daily['time'] : [];
        $days = [];

        foreach ($times as $index => $date) {
            $days[] = new WeatherDay(
                date: CarbonImmutable::parse((string) $date, $timezone)->startOfDay(),
                temperatureMax: $this->nullableFloat($daily['temperature_2m_max'][$index] ?? null),
                temperatureMin: $this->nullableFloat($daily['temperature_2m_min'][$index] ?? null),
                precipitationSumMm: (float) ($daily['precipitation_sum'][$index] ?? 0),
                precipitationProbabilityMax: $this->nullableInt($daily['precipitation_probability_max'][$index] ?? null),
                windSpeedMaxKmh: $this->nullableFloat($daily['wind_speed_10m_max'][$index] ?? null),
                windGustsMaxKmh: $this->nullableFloat($daily['wind_gusts_10m_max'][$index] ?? null),
                weatherCode: $this->nullableInt($daily['weather_code'][$index] ?? null),
            );
        }

        return $days;
    }

    private function consecutiveRainDuration(WeatherForecast $forecast, WeatherHour $first): int
    {
        $duration = 0;
        $started = false;

        foreach ($forecast->hours as $hour) {
            if (! $started && $hour->time->equalTo($first->time)) {
                $started = true;
            }

            if (! $started) {
                continue;
            }

            if (! $hour->isRainy((float) config('weather.rain.precipitation_threshold_mm', 0), (int) config('weather.rain.probability_threshold', 50))) {
                break;
            }

            $duration++;
        }

        return max(1, $duration);
    }

    /**
     * @param  list<WeatherHour>  $rainyBlocks
     */
    private function maxRainIntensity(array $rainyBlocks): string
    {
        $order = ['dry' => 0, 'light' => 1, 'moderate' => 2, 'heavy' => 3, 'very-heavy' => 4];
        $max = $rainyBlocks[0] ?? null;

        foreach ($rainyBlocks as $hour) {
            if ($max === null || $order[$hour->rainIntensityKey()] > $order[$max->rainIntensityKey()]) {
                $max = $hour;
            }
        }

        return $max?->rainIntensityLabel() ?? 'Dry';
    }

    private function daySummaryLine(?WeatherDay $day): string
    {
        if ($day === null) {
            return 'no forecast available';
        }

        return sprintf(
            '%s, %s-%s°C, %s mm, %s%% rain probability, wind %s km/h',
            $day->conditionLabel(),
            $day->temperatureMin !== null ? number_format($day->temperatureMin, 0, '.', '') : '?',
            $day->temperatureMax !== null ? number_format($day->temperatureMax, 0, '.', '') : '?',
            number_format($day->precipitationSumMm, 1, '.', ''),
            $day->precipitationProbabilityMax ?? 0,
            $day->windSpeedMaxKmh !== null ? number_format($day->windSpeedMaxKmh, 0, '.', '') : '?',
        );
    }

    private function rememberLastAlert(CarbonImmutable $sentAt, CarbonImmutable $firstAt, string $message, string $type): void
    {
        Cache::forever(self::LAST_ALERT_KEY, [
            'type' => $type,
            'sent_at' => $sentAt->toIso8601String(),
            'first_at' => $firstAt->toIso8601String(),
            'message' => $message,
        ]);
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
