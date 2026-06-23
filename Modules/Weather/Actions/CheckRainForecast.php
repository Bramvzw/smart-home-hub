<?php

namespace Modules\Weather\Actions;

use Carbon\CarbonImmutable;
use Modules\Weather\Data\RainAlertResult;
use Modules\Weather\Services\WeatherService;

class CheckRainForecast
{
    public function __construct(
        private readonly WeatherService $service,
    ) {}

    public function __invoke(?CarbonImmutable $now = null): RainAlertResult
    {
        return $this->service->checkRainAlert($now);
    }
}
