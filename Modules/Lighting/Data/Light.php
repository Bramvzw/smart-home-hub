<?php

namespace Modules\Lighting\Data;

final readonly class Light
{
    public function __construct(
        public string $provider,
        public string $id,
        public string $name,
        public bool $on,
        public int $brightness,        // 0-100
        public ?string $color,         // '#rrggbb' or null
        public bool $reachable,
        public bool $supportsColor,
    ) {}

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'id' => $this->id,
            'name' => $this->name,
            'on' => $this->on,
            'brightness' => $this->brightness,
            'color' => $this->color,
            'reachable' => $this->reachable,
            'supports_color' => $this->supportsColor,
        ];
    }
}
