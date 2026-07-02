<?php

namespace Modules\Lighting\Data;

use Carbon\CarbonImmutable;

final readonly class WeatherPresetTrigger
{
    public function __construct(
        public bool $enabled,
        public ?int $rainProbabilityMin = null,
        public ?float $precipitationMinMm = null,
        public ?float $temperatureMax = null,
        public string $startTime = '00:00',
        public string $endTime = '23:59',
    ) {}

    /**
     * True when $now (already in the forecast's local timezone) falls inside the
     * configured time window. Supports windows that wrap past midnight.
     */
    public function withinWindow(CarbonImmutable $now): bool
    {
        $start = $this->minutesSinceMidnight($this->startTime);
        $end = $this->minutesSinceMidnight($this->endTime);
        $current = ((int) $now->format('H')) * 60 + (int) $now->format('i');

        if ($start === $end) {
            return true;
        }

        if ($start < $end) {
            return $current >= $start && $current < $end;
        }

        return $current >= $start || $current < $end;
    }

    /**
     * All configured conditions must hold (AND). A condition that is not
     * configured (null) is ignored.
     */
    public function matches(float $currentPrecipitationMm, ?int $currentProbability, ?float $currentTemperature): bool
    {
        if ($this->rainProbabilityMin !== null
            && ($currentProbability === null || $currentProbability < $this->rainProbabilityMin)) {
            return false;
        }

        if ($this->precipitationMinMm !== null && $currentPrecipitationMm < $this->precipitationMinMm) {
            return false;
        }

        if ($this->temperatureMax !== null
            && ($currentTemperature === null || $currentTemperature > $this->temperatureMax)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{enabled: bool, rain_probability_min: int|null, precipitation_min_mm: float|null, temperature_max: float|null, start_time: string, end_time: string}
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'rain_probability_min' => $this->rainProbabilityMin,
            'precipitation_min_mm' => $this->precipitationMinMm,
            'temperature_max' => $this->temperatureMax,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];
    }

    private function minutesSinceMidnight(string $time): int
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        return max(0, min(24 * 60, ((int) $hour) * 60 + (int) $minute));
    }
}
