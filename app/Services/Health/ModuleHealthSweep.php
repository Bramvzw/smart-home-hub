<?php

namespace App\Services\Health;

use App\Contracts\ReportsHealth;
use App\Enums\ModuleHealthStatus;
use App\Services\ModuleRegistry;
use App\Services\Ntfy\HubNotifier;
use Illuminate\Support\Facades\Cache;

/**
 * Bit-rot detector: sweeps every health-reporting module and pushes an ntfy
 * notification when a module regresses (was ok, now isn't) or recovers.
 *
 * Only *transitions* notify — a module that is knowingly unconfigured stays
 * silent, and a cleared cache just re-baselines without a notification storm.
 */
class ModuleHealthSweep
{
    private const CACHE_KEY = 'health:sweep:statuses';

    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly HubNotifier $notifier,
    ) {}

    /**
     * @return array<string, string> module slug => health status value
     */
    public function __invoke(): array
    {
        $current = [];
        $issues = [];

        foreach ($this->registry->getModules() as $slug => $module) {
            if (! $module instanceof ReportsHealth) {
                continue;
            }

            $health = $module->health();
            $current[$slug] = $health->status->value;

            if (! $health->isOk()) {
                $issues[$slug] = $health->issues;
            }
        }

        /** @var array<string, string> $previous */
        $previous = Cache::get(self::CACHE_KEY, []);
        Cache::forever(self::CACHE_KEY, $current);

        $ok = ModuleHealthStatus::Ok->value;

        $regressed = array_values(array_filter(
            array_keys($issues),
            static fn (string $slug): bool => ($previous[$slug] ?? null) === $ok,
        ));
        $recovered = array_values(array_filter(
            array_keys($current),
            static fn (string $slug): bool => $current[$slug] === $ok
                && isset($previous[$slug]) && $previous[$slug] !== $ok,
        ));

        if ($regressed !== []) {
            $lines = array_map(
                static fn (string $slug): string => $slug.': '.implode('; ', $issues[$slug]),
                $regressed,
            );
            $this->notifier->sendWithOptions('Module health', implode("\n", $lines), 'warning', '4');
        }

        if ($recovered !== []) {
            $this->notifier->sendWithOptions('Module health recovered', implode(', ', $recovered), 'white_check_mark', '3');
        }

        return $current;
    }
}
