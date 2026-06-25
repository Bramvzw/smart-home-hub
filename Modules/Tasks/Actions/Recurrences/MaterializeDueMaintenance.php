<?php

namespace Modules\Tasks\Actions\Recurrences;

use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Modules\Tasks\Models\TaskBoard;
use Modules\Tasks\Models\TaskColumn;
use Modules\Tasks\Models\TaskRecurrence;

class MaterializeDueMaintenance
{
    public function __construct(
        private readonly HubNotifier $notifier,
    ) {
    }

    public function __invoke(?CarbonInterface $date = null): int
    {
        $date = $this->date($date);
        $created = 0;

        TaskRecurrence::query()
            ->maintenance()
            ->active()
            ->dueOn($date)
            ->orderBy('next_due_on')
            ->get()
            ->each(function (TaskRecurrence $recurrence) use (&$created, $date): void {
                if ($this->materialize($recurrence, $date)) {
                    $created++;
                }
            });

        return $created;
    }

    private function materialize(TaskRecurrence $recurrence, CarbonImmutable $date): bool
    {
        return DB::transaction(function () use ($recurrence, $date): bool {
            /** @var TaskRecurrence|null $locked */
            $locked = TaskRecurrence::query()->whereKey($recurrence->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->active || ! $locked->isMaintenance() || ! $locked->next_due_on) {
                return false;
            }

            $dueOn = CarbonImmutable::instance($locked->next_due_on)->startOfDay();

            if ($dueOn->greaterThan($date)) {
                return false;
            }

            if ($locked->last_materialized_on?->isSameDay($dueOn)) {
                return false;
            }

            $board = $this->maintenanceBoard($locked);
            $column = $this->maintenanceColumn($board);

            $task = $board->tasks()->create([
                'column_id' => $column->id,
                'recurrence_id' => $locked->id,
                'title' => $locked->title,
                'description' => $locked->description ?? '',
                'priority' => 'normal',
                'due_date' => $dueOn->toDateString(),
                'completed' => $column->isDoneColumn(),
                'position' => (int) $column->tasks()->max('position') + 1,
            ]);

            $locked->forceFill(['last_materialized_on' => $dueOn->toDateString()])->save();

            if ((bool) config('tasks.recurrence.notify', true) && $locked->notify) {
                $this->notifier->send('Onderhoudstaak', $task->title.' is ingepland voor '.$dueOn->format('d-m-Y').'.');
            }

            return true;
        });
    }

    private function maintenanceBoard(TaskRecurrence $recurrence): TaskBoard
    {
        if ($recurrence->board) {
            return $recurrence->board;
        }

        $name = (string) config('tasks.recurrence.maintenance_board', 'Tasks');
        $board = TaskBoard::query()->firstOrCreate(
            ['name' => $name],
            ['position' => (int) TaskBoard::query()->max('position') + 1]
        );

        $this->ensureDefaultColumns($board);

        return $board;
    }

    private function maintenanceColumn(TaskBoard $board): TaskColumn
    {
        $name = (string) config('tasks.recurrence.maintenance_column', 'Todo');
        $column = $board->columns()->where('name', $name)->first();

        if ($column) {
            return $column;
        }

        return $board->columns()->create([
            'name' => $name,
            'position' => (int) $board->columns()->max('position') + 1,
        ]);
    }

    private function ensureDefaultColumns(TaskBoard $board): void
    {
        foreach (['Todo', 'Doing', 'Done'] as $position => $name) {
            $board->columns()->firstOrCreate(
                ['name' => $name],
                ['position' => $position]
            );
        }
    }

    private function date(?CarbonInterface $date = null): CarbonImmutable
    {
        if ($date) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        return CarbonImmutable::now((string) config('app.timezone', 'UTC'))->startOfDay();
    }
}
