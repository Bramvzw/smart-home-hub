<?php

namespace Modules\Lighting\Data;

final readonly class LightPreset
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $power,
        public ?int $brightness = null,
        public ?string $color = null,
    ) {}

    /**
     * @return array{key: string, label: string, power: bool, brightness: int|null, color: string|null}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'power' => $this->power,
            'brightness' => $this->brightness,
            'color' => $this->color,
        ];
    }
}
