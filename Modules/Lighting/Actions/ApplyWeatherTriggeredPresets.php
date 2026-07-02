<?php

namespace Modules\Lighting\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Lighting\Data\LightPreset;
use Modules\Lighting\Services\LightingService;
use Modules\Weather\Data\WeatherForecast;
use Modules\Weather\Data\WeatherHour;
use Modules\Weather\Services\WeatherService;
use Throwable;

/**
 * Evaluates opt-in weather triggers on lighting presets (e.g. rain -> Cozy)
 * and applies the matching presets via the existing preset-apply flow.
 *
 * Anti-flapping: once a trigger fires, a "period active" marker is kept until
 * the conditions no longer match, so the preset is (re)applied at most once
 * per continuous trigger period and manual control in between is respected.
 * This mirrors the Weather module's rain-alert "one notification per period"
 * pattern (Modules\Weather\Services\WeatherService::checkRainAlert).
 */
class ApplyWeatherTriggeredPresets
{
    private const ACTIVE_KEY_PREFIX = 'lighting:weather-preset:active:';

    public function __construct(
        private readonly LightingService $lightingService,
        private readonly WeatherService $weatherService,
    ) {}

    /**
     * @return list<string> keys of presets that were (re)applied on this run
     */
    public function __invoke(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        $triggeredPresets = array_values(array_filter(
            $this->lightingService->presets(),
            static fn (LightPreset $preset): bool => $preset->weatherTrigger !== null && $preset->weatherTrigger->enabled,
        ));

        if ($triggeredPresets === []) {
            return [];
        }

        // Reuses WeatherService's own cache, so this never forces an extra API call.
        $forecast = $this->weatherService->forecast($now);
        $currentHour = $this->currentHour($forecast, $now);
        $applied = [];

        foreach ($triggeredPresets as $preset) {
            $activeKey = self::ACTIVE_KEY_PREFIX.$preset->key;
            $matches = $preset->weatherTrigger->withinWindow($now->setTimezone($forecast->timezone))
                && $preset->weatherTrigger->matches(
                    $forecast->currentPrecipitationMm,
                    $currentHour?->precipitationProbability,
                    $forecast->currentTemperature,
                );

            if (! $matches) {
                // Trigger period ended (or never started): clear the marker so the
                // next matching period is treated as a fresh trigger.
                Cache::forget($activeKey);

                continue;
            }

            if (Cache::has($activeKey)) {
                // Already applied for this ongoing trigger period.
                continue;
            }

            try {
                $this->lightingService->applyPreset($preset->key);
            } catch (Throwable) {
                // Leave the marker unset so the next scheduled run retries.
                continue;
            }

            Cache::forever($activeKey, true);
            $applied[] = $preset->key;
        }

        return $applied;
    }

    private function currentHour(WeatherForecast $forecast, CarbonImmutable $now): ?WeatherHour
    {
        $target = $now->setTimezone($forecast->timezone)->startOfHour();

        foreach ($forecast->hours as $hour) {
            if ($hour->time->equalTo($target)) {
                return $hour;
            }
        }

        return null;
    }
}
