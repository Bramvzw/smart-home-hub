<?php

namespace Modules\Tasks\Data;

final readonly class HabitProgress
{
    public function __construct(
        public string $periodKey,
        public int $completed,
        public int $target,
    ) {
    }

    public function isComplete(): bool
    {
        return $this->target > 0 && $this->completed >= $this->target;
    }

    public function percentage(): int
    {
        if ($this->target <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->completed / $this->target) * 100));
    }

    public function toArray(): array
    {
        return [
            'period_key' => $this->periodKey,
            'completed' => $this->completed,
            'target' => $this->target,
            'is_complete' => $this->isComplete(),
            'percentage' => $this->percentage(),
        ];
    }
}
