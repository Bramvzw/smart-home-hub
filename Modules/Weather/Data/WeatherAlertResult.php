<?php

namespace Modules\Weather\Data;

final readonly class WeatherAlertResult
{
    /**
     * @param  list<WeatherHour>  $blocks
     */
    public function __construct(
        public WeatherForecast $forecast,
        public array $blocks,
        public bool $notified,
        public string $status,
        public string $message,
    ) {}
}
