<?php

namespace Modules\Tasks\Services;

use App\Contracts\SchedulableGoals;
use App\Data\HabitCard;
use App\Data\SchedulableGoal;
use Carbon\CarbonInterface;
use Modules\Tasks\Actions\Recurrences\CompleteHabit;
use Modules\Tasks\Actions\Recurrences\CreateRecurrence;
use Modules\Tasks\Actions\Recurrences\DeleteRecurrence;
use Modules\Tasks\Actions\Recurrences\UndoHabitCompletion;
use Modules\Tasks\Actions\Recurrences\UpdateRecurrence;
use Modules\Tasks\Models\TaskRecurrence;

/**
 * Tasks-side implementation of the SchedulableGoals contract: habit
 * TaskRecurrences double as the planner's schedulable goals. Planner-only
 * metadata (category, target range, preferred windows) rides along in
 * cadence_config; `plannable` + `duration_minutes` are real columns.
 */
class RecurrenceGoals implements SchedulableGoals
{
    private const DEFAULT_DURATION = 60;

    public function __construct(
        private readonly CreateRecurrence $createRecurrence,
        private readonly UpdateRecurrence $updateRecurrence,
        private readonly DeleteRecurrence $deleteRecurrence,
        private readonly HabitCardPresenter $presenter,
        private readonly CompleteHabit $completeHabit,
        private readonly UndoHabitCompletion $undoHabitCompletion,
    ) {}

    public function plannable(): array
    {
        return TaskRecurrence::query()
            ->habits()
            ->active()
            ->plannable()
            ->orderBy('title')
            ->get()
            ->map(fn (TaskRecurrence $r): SchedulableGoal => $this->toGoal($r))
            ->all();
    }

    public function cards(CarbonInterface $date): array
    {
        return TaskRecurrence::query()
            ->habits()
            ->orderBy('title')
            ->get()
            ->map(fn (TaskRecurrence $r): HabitCard => $this->presenter->present($r, $date))
            ->all();
    }

    public function complete(int $id, CarbonInterface $date): HabitCard
    {
        $recurrence = TaskRecurrence::query()->habits()->findOrFail($id);
        ($this->completeHabit)($recurrence, $date);

        return $this->presenter->present($recurrence->fresh(), $date);
    }

    public function undo(int $id, CarbonInterface $date): HabitCard
    {
        $recurrence = TaskRecurrence::query()->habits()->findOrFail($id);
        ($this->undoHabitCompletion)($recurrence, $date);

        return $this->presenter->present($recurrence->fresh(), $date);
    }

    public function all(): array
    {
        return TaskRecurrence::query()
            ->habits()
            ->plannable()
            ->orderBy('title')
            ->get()
            ->map(fn (TaskRecurrence $r): SchedulableGoal => $this->toGoal($r))
            ->all();
    }

    public function find(int $id): ?SchedulableGoal
    {
        $recurrence = TaskRecurrence::query()->habits()->find($id);

        return $recurrence ? $this->toGoal($recurrence) : null;
    }

    public function create(array $attributes): SchedulableGoal
    {
        $recurrence = ($this->createRecurrence)($this->toAttributes($attributes));

        return $this->toGoal($recurrence);
    }

    public function update(int $id, array $attributes): SchedulableGoal
    {
        $recurrence = TaskRecurrence::query()->habits()->findOrFail($id);
        $updated = ($this->updateRecurrence)($recurrence, $this->toAttributes($attributes, $recurrence));

        return $this->toGoal($updated);
    }

    public function delete(int $id): void
    {
        $recurrence = TaskRecurrence::query()->habits()->findOrFail($id);
        ($this->deleteRecurrence)($recurrence);
    }

    public function setActive(int $id, bool $active): SchedulableGoal
    {
        $recurrence = TaskRecurrence::query()->habits()->findOrFail($id);
        $updated = ($this->updateRecurrence)($recurrence, ['active' => $active]);

        return $this->toGoal($updated);
    }

    public function setPlannable(int $id, bool $plannable): SchedulableGoal
    {
        $recurrence = TaskRecurrence::query()->habits()->findOrFail($id);
        $updated = ($this->updateRecurrence)($recurrence, ['plannable' => $plannable]);

        return $this->toGoal($updated);
    }

    private function toGoal(TaskRecurrence $r): SchedulableGoal
    {
        $config = $r->cadence_config ?? [];
        [$min, $max] = $this->targetRange($r, $config);

        return new SchedulableGoal(
            id: $r->id,
            title: $r->title,
            category: (string) ($config['category'] ?? 'custom'),
            frequencyType: $r->cadence_type === 'weekly' ? 'weekly' : 'times_per_week',
            durationMinutes: $r->duration_minutes ?? self::DEFAULT_DURATION,
            targetMin: $min,
            targetMax: $max,
            preferredWindows: is_array($config['preferred_windows'] ?? null) ? $config['preferred_windows'] : [],
            active: (bool) $r->active,
        );
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function targetRange(TaskRecurrence $r, array $config): array
    {
        $fromCadence = match ($r->cadence_type) {
            'times_per_week' => max(1, (int) ($config['times'] ?? $config['target'] ?? $config['count'] ?? 1)),
            'weekdays' => max(1, count($this->weekdays($config))),
            default => 1,
        };

        $min = max(1, (int) ($config['target_min'] ?? $fromCadence));
        $max = max($min, (int) ($config['target_max'] ?? $fromCadence));

        return [$min, $max];
    }

    /**
     * Map incoming goal attributes onto TaskRecurrence attributes. When updating,
     * merge onto the existing cadence_config so untouched planner hints survive.
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    private function toAttributes(array $in, ?TaskRecurrence $existing = null): array
    {
        $config = $existing->cadence_config ?? [];

        if (array_key_exists('category', $in)) {
            $config['category'] = (string) $in['category'];
        }
        if (array_key_exists('preferred_windows', $in)) {
            $config['preferred_windows'] = is_array($in['preferred_windows']) ? $in['preferred_windows'] : [];
        }

        $min = isset($in['target_min']) ? max(1, (int) $in['target_min']) : ($config['target_min'] ?? 1);
        $max = isset($in['target_max']) ? max((int) $min, (int) $in['target_max']) : ($config['target_max'] ?? $min);
        $config['target_min'] = (int) $min;
        $config['target_max'] = (int) $max;
        // The streak target (times/week) tracks the commitment floor.
        $config['times'] = (int) $min;

        $attributes = [
            'type' => 'habit',
            'cadence_config' => $config,
            'plannable' => $in['plannable'] ?? ($existing->plannable ?? true),
        ];

        if (array_key_exists('title', $in)) {
            $attributes['title'] = (string) $in['title'];
        }
        if (array_key_exists('active', $in)) {
            $attributes['active'] = (bool) $in['active'];
        }
        if (array_key_exists('duration_minutes', $in)) {
            $attributes['duration_minutes'] = (int) $in['duration_minutes'];
        }
        if (array_key_exists('frequency_type', $in)) {
            $attributes['cadence_type'] = $in['frequency_type'] === 'weekly' ? 'weekly' : 'times_per_week';
        } elseif (! $existing) {
            $attributes['cadence_type'] = 'times_per_week';
        }

        return $attributes;
    }

    /**
     * @return list<int>
     */
    private function weekdays(array $config): array
    {
        $days = $config['weekdays'] ?? $config['days'] ?? [];

        return is_array($days) ? array_values($days) : [];
    }
}
