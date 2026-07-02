<?php

namespace App\Contracts;

use App\Data\HabitCard;
use App\Data\SchedulableGoal;
use Carbon\CarbonInterface;

/**
 * Directory of habits: the ones the weekly planner schedules (plannable) plus
 * streak tracking and completion.
 *
 * Implemented by the module that owns habits (Modules/Tasks, backed by habit
 * TaskRecurrences) and consumed by the Calendar module (planner + Habits tab).
 * Neither module imports the other's namespace — this contract in app/ is the
 * seam, mirroring how BriefingSource decouples the Briefing module from its sources.
 */
interface SchedulableGoals
{
    /**
     * Active goals the planner should place time blocks for.
     *
     * @return list<SchedulableGoal>
     */
    public function plannable(): array;

    /**
     * Every habit as a full card (management + streak/tracking) for the given day.
     *
     * @return list<HabitCard>
     */
    public function cards(CarbonInterface $date): array;

    /**
     * Every goal (active or not), for the management view.
     *
     * @return list<SchedulableGoal>
     */
    public function all(): array;

    public function find(int $id): ?SchedulableGoal;

    public function complete(int $id, CarbonInterface $date): HabitCard;

    public function undo(int $id, CarbonInterface $date): HabitCard;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): SchedulableGoal;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(int $id, array $attributes): SchedulableGoal;

    public function delete(int $id): void;

    public function setActive(int $id, bool $active): SchedulableGoal;

    public function setPlannable(int $id, bool $plannable): SchedulableGoal;
}
