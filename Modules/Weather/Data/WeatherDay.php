<?php

namespace Modules\Weather\Data;

use Carbon\CarbonImmutable;
use Modules\Weather\Support\WeatherCode;

final readonly class WeatherDay
{
    public function __construct(
        public CarbonImmutable $date,
        public ?float $temperatureMax,
        public ?float $temperatureMin,
        public float $precipitationSumMm,
        public ?int $precipitationProbabilityMax,
        public ?float $windSpeedMaxKmh,
        public ?float $windGustsMaxKmh,
        public ?int $weatherCode,
    ) {}

    public function conditionLabel(): string
    {
        return WeatherCode::label($this->weatherCode);
    }
}
