<?php

namespace Modules\Weather\Data;

use Carbon\CarbonImmutable;
use Modules\Weather\Support\WeatherCode;

final readonly class WeatherHour
{
    public function __construct(
        public CarbonImmutable $time,
        public ?float $temperature,
        public float $precipitationMm,
        public ?int $precipitationProbability,
        public ?int $weatherCode,
        public ?float $windSpeedKmh = null,
        public ?float $windGustsKmh = null,
    ) {}

    public function isRainy(float $precipitationThresholdMm, int $probabilityThreshold): bool
    {
        return $this->precipitationMm > $precipitationThresholdMm
            || ($this->precipitationProbability !== null && $this->precipitationProbability >= $probabilityThreshold);
    }

    public function conditionLabel(): string
    {
        return WeatherCode::label($this->weatherCode);
    }

    public function rainIntensityKey(): string
    {
        return match (true) {
            $this->precipitationMm <= 0 => 'dry',
            $this->precipitationMm < 1 => 'light',
            $this->precipitationMm < 3 => 'moderate',
            $this->precipitationMm < 7 => 'heavy',
            default => 'very-heavy',
        };
    }

    public function rainIntensityLabel(): string
    {
        return match ($this->rainIntensityKey()) {
            'light' => 'Light rain',
            'moderate' => 'Moderate rain',
            'heavy' => 'Heavy rain',
            'very-heavy' => 'Very heavy rain',
            default => 'Dry',
        };
    }

    public function isWindy(float $thresholdKmh): bool
    {
        return ($this->windSpeedKmh !== null && $this->windSpeedKmh >= $thresholdKmh)
            || ($this->windGustsKmh !== null && $this->windGustsKmh >= $thresholdKmh);
    }
}
