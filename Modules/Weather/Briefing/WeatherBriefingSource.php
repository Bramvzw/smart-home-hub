<?php

namespace Modules\Weather\Briefing;

use App\Contracts\BriefingSource;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Modules\Weather\Services\WeatherService;

class WeatherBriefingSource implements BriefingSource
{
    public function __construct(
        private readonly WeatherService $service,
    ) {}

    public function key(): string
    {
        return 'weather';
    }

    public function label(): string
    {
        return 'Weer';
    }

    public function priority(): int
    {
        return 10;
    }

    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        if (! $this->service->isConfigured()) {
            return null;
        }

        $forecast = $this->service->forecast($date);
        $today = $forecast->today();

        if ($today === null) {
            return null;
        }

        $rainEvent = $this->service->rainEvent($forecast, $date);
        $windEvent = $this->service->windEvent($forecast, $date);
        $temp = $this->temperatureRange($today->temperatureMin, $today->temperatureMax);
        $rain = $rainEvent
            ? 'regen vanaf '.$rainEvent['first']->time->format('H:i')
            : 'geen regenalarm';
        $wind = $windEvent
            ? 'wind vanaf '.$windEvent['first']->time->format('H:i')
            : 'geen windalarm';

        return new BriefingSection(
            key: $this->key(),
            label: $this->label(),
            priority: $this->priority(),
            summary: "{$temp}, {$today->conditionLabel()}, {$rain}, {$wind}",
            data: [
                'location' => $forecast->locationLabel,
                'condition' => $today->conditionLabel(),
                'temperature_min' => $today->temperatureMin,
                'temperature_max' => $today->temperatureMax,
                'precipitation_sum_mm' => $today->precipitationSumMm,
                'precipitation_probability_max' => $today->precipitationProbabilityMax,
                'rain_event' => $rainEvent ? [
                    'starts_at' => $rainEvent['first']->time->toIso8601String(),
                    'duration_hours' => $rainEvent['duration_hours'],
                    'total_mm' => $rainEvent['total_mm'],
                    'max_probability' => $rainEvent['max_probability'],
                    'intensity' => $rainEvent['intensity'],
                ] : null,
                'wind_event' => $windEvent ? [
                    'starts_at' => $windEvent['first']->time->toIso8601String(),
                    'max_wind_kmh' => $windEvent['max_wind'],
                    'max_gusts_kmh' => $windEvent['max_gusts'],
                ] : null,
            ],
        );
    }

    private function temperatureRange(?float $min, ?float $max): string
    {
        if ($min === null && $max === null) {
            return 'temperatuur onbekend';
        }

        if ($min === null) {
            return 'max '.number_format((float) $max, 0, ',', '').'°C';
        }

        if ($max === null) {
            return 'min '.number_format($min, 0, ',', '').'°C';
        }

        return number_format($min, 0, ',', '').'-'.number_format($max, 0, ',', '').'°C';
    }
}
