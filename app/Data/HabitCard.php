<?php

namespace App\Data;

/**
 * A habit as shown on the Calendar "Habits" tab: management fields (for editing
 * and planning) plus tracking fields (streak, weekly progress, completion state).
 *
 * Cross-module contract shape — the tab consumes these without knowing they are
 * backed by TaskRecurrences in Modules/Tasks.
 */
final readonly class HabitCard
{
    /**
     * @param  list<array<string, mixed>>  $preferredWindows
     * @param  list<array{label: string, status: string, today: bool}>  $week
     */
    public function __construct(
        // Management
        public int $id,
        public string $title,
        public string $category,
        public string $frequencyType,
        public int $durationMinutes,
        public int $targetMin,
        public int $targetMax,
        public array $preferredWindows,
        public bool $plannable,
        public bool $active,
        // Tracking
        public string $icon,
        public string $cadenceLabel,
        public string $type,
        public int $target,
        public int $done,
        public bool $reached,
        public array $week,
        public int $weekDone,
        public int $weekTotal,
        public int $streak,
        public int $best,
        public bool $completedToday,
        public bool $restToday,
    ) {}

    public function toSchedulableGoal(): SchedulableGoal
    {
        return new SchedulableGoal(
            id: $this->id,
            title: $this->title,
            category: $this->category,
            frequencyType: $this->frequencyType,
            durationMinutes: $this->durationMinutes,
            targetMin: $this->targetMin,
            targetMax: $this->targetMax,
            preferredWindows: $this->preferredWindows,
            active: $this->active,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'frequency_type' => $this->frequencyType,
            'duration_minutes' => $this->durationMinutes,
            'target_min' => $this->targetMin,
            'target_max' => $this->targetMax,
            'preferred_windows' => $this->preferredWindows,
            'plannable' => $this->plannable,
            'active' => $this->active,
            'icon' => $this->icon,
            'cadence_label' => $this->cadenceLabel,
            'type' => $this->type,
            'target' => $this->target,
            'done' => $this->done,
            'reached' => $this->reached,
            'week' => $this->week,
            'week_done' => $this->weekDone,
            'week_total' => $this->weekTotal,
            'streak' => $this->streak,
            'best' => $this->best,
            'completed_today' => $this->completedToday,
            'rest_today' => $this->restToday,
        ];
    }
}
