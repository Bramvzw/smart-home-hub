<?php

namespace Modules\Briefing\Actions;

use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Modules\Briefing\Models\Briefing;
use Modules\Briefing\Services\BriefingComposer;
use Modules\Briefing\Services\BriefingSourceRegistry;

class GenerateBriefing
{
    public function __construct(
        private readonly BriefingSourceRegistry $sources,
        private readonly BriefingComposer $composer,
        private readonly HubNotifier $notifier,
    ) {}

    public function __invoke(?CarbonImmutable $date = null, bool $push = true): Briefing
    {
        $timezone = (string) config('briefing.timezone', 'Europe/Amsterdam');
        $date = ($date ?? CarbonImmutable::now($timezone))->setTimezone($timezone)->startOfDay();
        $sections = $this->sources->sections($date);
        $composed = $this->composer->compose($sections);
        $generatedAt = CarbonImmutable::now($timezone);

        $briefing = Briefing::query()->updateOrCreate(
            ['date' => $date->toDateString()],
            [
                'body' => $composed->body,
                'sections' => array_map(fn ($section): array => $section->toArray(), $sections),
                'generated_at' => $generatedAt->utc(),
                'model' => $composed->model,
                'is_fallback' => $composed->isFallback,
            ],
        );

        $this->prune($date);

        if ($push) {
            $this->notifier->send('Goedemorgen', $this->notificationBody($briefing->body));
        }

        return $briefing->refresh();
    }

    private function prune(CarbonImmutable $date): void
    {
        $retentionDays = max(1, (int) config('briefing.retention_days', 14));

        Briefing::query()
            ->where('date', '<', $date->subDays($retentionDays)->toDateString())
            ->delete();
    }

    private function notificationBody(string $body): string
    {
        $limit = max(500, (int) config('briefing.ntfy_max_length', 3900));

        return Str::limit($body, $limit, '...');
    }
}
