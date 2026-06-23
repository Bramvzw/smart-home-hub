<?php

namespace Modules\Weather\Data;

use Carbon\CarbonImmutable;
use Modules\Weather\Support\WeatherCode;

final readonly class WeatherForecast
{
    /**
     * @param  list<WeatherHour>  $hours
     * @param  list<WeatherDay>  $days
     */
    public function __construct(
        public string $locationLabel,
        public float $latitude,
        public float $longitude,
        public string $timezone,
        public CarbonImmutable $fetchedAt,
        public array $hours,
        public array $days,
        public ?float $currentTemperature,
        public float $currentPrecipitationMm,
        public ?int $currentWeatherCode,
        public ?float $currentWindSpeedKmh,
        public ?float $currentWindGustsKmh,
    ) {}

    /**
     * @return list<WeatherHour>
     */
    public function hourlyWindow(CarbonImmutable $now, int $windowHours): array
    {
        $start = $now->setTimezone($this->timezone)->startOfHour();
        $end = $start->addHours(max(1, $windowHours));

        return array_values(array_filter(
            $this->hours,
            static fn (WeatherHour $hour): bool => $hour->time->greaterThanOrEqualTo($start)
                && $hour->time->lessThanOrEqualTo($end),
        ));
    }

    /**
     * @return list<WeatherHour>
     */
    public function rainyBlocks(CarbonImmutable $now, int $windowHours, float $precipitationThresholdMm, int $probabilityThreshold): array
    {
        return array_values(array_filter(
            $this->hourlyWindow($now, $windowHours),
            static fn (WeatherHour $hour): bool => $hour->isRainy($precipitationThresholdMm, $probabilityThreshold),
        ));
    }

    public function currentConditionLabel(): string
    {
        return WeatherCode::label($this->currentWeatherCode);
    }

    public function today(): ?WeatherDay
    {
        return $this->days[0] ?? null;
    }

    public function tomorrow(): ?WeatherDay
    {
        return $this->days[1] ?? null;
    }
}
