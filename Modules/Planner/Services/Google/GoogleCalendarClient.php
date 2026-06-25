<?php

namespace Modules\Planner\Services\Google;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;
use Modules\Planner\Data\BusyTime;
use Modules\Planner\Models\PlannerPlanItem;
use RuntimeException;

class GoogleCalendarClient
{
    public function __construct(private readonly GoogleCalendarTokenService $tokens) {}

    /**
     * @return list<BusyTime>
     */
    public function busyTimes(CarbonPeriod $period): array
    {
        $token = $this->tokens->accessToken();

        if (! $token) {
            return [];
        }

        $start = CarbonImmutable::instance($period->getStartDate());
        $end = CarbonImmutable::instance($period->getEndDate());
        $calendarId = (string) config('planner.google.calendar_id', 'primary');
        $response = Http::withToken($token)->acceptJson()->post('https://www.googleapis.com/calendar/v3/freeBusy', [
            'timeMin' => $start->toIso8601String(),
            'timeMax' => $end->toIso8601String(),
            'items' => [['id' => $calendarId]],
        ])->throw()->json();

        return collect(data_get($response, "calendars.{$calendarId}.busy", []))
            ->map(fn (array $busy): BusyTime => new BusyTime(CarbonImmutable::parse($busy['start']), CarbonImmutable::parse($busy['end'])))
            ->values()
            ->all();
    }

    public function insertEvent(PlannerPlanItem $item): string
    {
        if (! $item->start_at || ! $item->end_at) {
            throw new RuntimeException('Cannot insert an unplaced planner item.');
        }

        $token = $this->tokens->accessToken();

        if (! $token) {
            throw new RuntimeException('Google Calendar is not connected.');
        }

        $calendarId = (string) config('planner.google.calendar_id', 'primary');
        $response = Http::withToken($token)->acceptJson()->post("https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events", [
            'summary' => $item->title,
            'start' => ['dateTime' => $item->start_at->toIso8601String()],
            'end' => ['dateTime' => $item->end_at->toIso8601String()],
        ])->throw()->json();

        return (string) ($response['id'] ?? '');
    }
}
