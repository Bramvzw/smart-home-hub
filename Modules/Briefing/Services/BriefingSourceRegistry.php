<?php

namespace Modules\Briefing\Services;

use App\Contracts\BriefingSource;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use Throwable;

class BriefingSourceRegistry
{
    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * @return list<BriefingSection>
     */
    public function sections(CarbonImmutable $date): array
    {
        $sections = [];

        foreach ($this->container->tagged('briefing.source') as $source) {
            if (! $source instanceof BriefingSource) {
                continue;
            }

            try {
                $section = $source->contribute($date);
            } catch (Throwable $exception) {
                Log::warning('Briefing source failed.', [
                    'source' => $source->key(),
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            if ($section !== null) {
                $sections[] = $section;
            }
        }

        usort($sections, static fn (BriefingSection $a, BriefingSection $b): int => $a->priority <=> $b->priority);

        return $sections;
    }
}
