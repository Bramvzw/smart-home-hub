<?php

namespace Modules\Tasks\Actions\Recurrences;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Modules\Tasks\Models\TaskRecurrence;
use Modules\Tasks\Services\MaintenanceScheduler;

/**
 * Completes a recurring maintenance task directly from the habits page (rather
 * than via a Kanban card move): records the completion, advances the next due
 * date one period, and clears any materialized board card so it disappears
 * until it is due again.
 */
class CompleteMaintenance
{
    public function __construct(
        private readonly MaintenanceScheduler $scheduler,
    ) {
    }

    public function __invoke(TaskRecurrence $recurrence, ?CarbonInterface $completedOn = null): ?TaskRecurrence
    {
        if (! $recurrence->isMaintenance()) {
            return null;
        }

        $completedOn = $this->date($completedOn);
        $periodKey = $completedOn->toDateString();

        return DB::transaction(function () use ($recurrence, $completedOn, $periodKey): TaskRecurrence {
            $recurrence->completions()->firstOrCreate(
                ['period_key' => $periodKey],
                ['completed_on' => $completedOn->toDateString()]
            );

            $recurrence->tasks()
                ->whereNull('archived_at')
                ->update(['archived_at' => now()]);

            $recurrence->forceFill([
                'next_due_on' => $this->scheduler->nextDueOn($recurrence, $completedOn)->toDateString(),
                'last_materialized_on' => null,
            ])->save();

            return $recurrence->fresh();
        });
    }

    private function date(?CarbonInterface $date = null): CarbonImmutable
    {
        if ($date) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        return CarbonImmutable::now((string) config('app.timezone', 'UTC'))->startOfDay();
    }
}
