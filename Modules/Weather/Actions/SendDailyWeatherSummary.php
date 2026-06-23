<?php

namespace Modules\Weather\Actions;

use Carbon\CarbonImmutable;
use Modules\Weather\Data\WeatherAlertResult;
use Modules\Weather\Services\WeatherService;

class SendDailyWeatherSummary
{
    public function __construct(
        private readonly WeatherService $service,
    ) {}

    public function __invoke(?CarbonImmutable $now = null): WeatherAlertResult
    {
        return $this->service->sendDailySummary($now);
    }
}
