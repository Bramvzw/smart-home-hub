<?php

namespace App\Data;

/**
 * A recurring personal goal that the weekly planner can schedule into free time.
 *
 * This is the cross-module contract shape: the planner (Modules/Calendar) consumes
 * these without knowing they are backed by habits (Modules/Tasks/TaskRecurrence).
 * `id` is the backing recurrence id.
 *
 * @phpstan-type Window array{days?: string, after?: string, before?: string, start?: string, end?: string}
 */
final readonly class SchedulableGoal
{
    /**
     * @param  list<Window>  $preferredWindows
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $category,
        public string $frequencyType,
        public int $durationMinutes,
        public int $targetMin,
        public int $targetMax,
        public array $preferredWindows,
        public bool $active,
    ) {}

    /**
     * View/JSON shape for the management tab (mirrors the old intention contract).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'frequency_type' => $this->frequencyType,
            'target_min' => $this->targetMin,
            'target_max' => $this->targetMax,
            'preferred_windows' => $this->preferredWindows,
            'duration_minutes' => $this->durationMinutes,
            'active' => $this->active,
        ];
    }
}
