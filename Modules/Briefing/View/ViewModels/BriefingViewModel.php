<?php

namespace Modules\Briefing\View\ViewModels;

use Carbon\CarbonImmutable;
use Modules\Briefing\Models\Briefing;

class BriefingViewModel
{
    public function today(?CarbonImmutable $date = null): array
    {
        $timezone = (string) config('briefing.timezone', 'Europe/Amsterdam');
        $date = ($date ?? CarbonImmutable::now($timezone))->setTimezone($timezone);
        $briefing = Briefing::query()->whereDate('date', $date->toDateString())->first();

        return [
            'date' => $date->toDateString(),
            'briefing' => $briefing,
            'hasBriefing' => $briefing !== null,
        ];
    }
}
