<?php

namespace Modules\Tasks\Actions\Recurrences;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskRecurrence;
use Modules\Tasks\Services\MaintenanceScheduler;

class CompleteMaintenanceCard
{
    public function __construct(
        private readonly MaintenanceScheduler $scheduler,
    ) {
    }

    public function __invoke(KanbanTask $task, ?CarbonInterface $completedOn = null): ?TaskRecurrence
    {
        if (! $task->completed || ! $task->recurrence_id) {
            return null;
        }

        $recurrence = $task->recurrence()->first();

        if (! $recurrence || ! $recurrence->isMaintenance()) {
            return null;
        }

        $completedOn = $this->date($completedOn);
        $periodKey = $completedOn->toDateString();

        return DB::transaction(function () use ($recurrence, $completedOn, $periodKey): ?TaskRecurrence {
            $recurrence->completions()->firstOrCreate(
                ['period_key' => $periodKey],
                ['completed_on' => $completedOn->toDateString()]
            );

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
