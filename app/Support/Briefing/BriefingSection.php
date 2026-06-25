<?php

namespace App\Support\Briefing;

final readonly class BriefingSection
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $key,
        public string $label,
        public int $priority,
        public string $summary,
        public array $data = [],
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'priority' => $this->priority,
            'summary' => $this->summary,
            'data' => $this->data,
        ];
    }
}
