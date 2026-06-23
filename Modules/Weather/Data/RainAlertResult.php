<?php

namespace Modules\Weather\Data;

final readonly class RainAlertResult
{
    /**
     * @param  list<WeatherHour>  $rainyBlocks
     */
    public function __construct(
        public WeatherForecast $forecast,
        public array $rainyBlocks,
        public bool $notified,
        public string $status,
        public string $message,
    ) {}
}
