<?php

namespace Modules\Lighting\Data;

use Modules\Lighting\Enums\LightMode;

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
        public LightMode $mode = LightMode::White,
        public ?int $colorTemp = null, // 0 (warm) - 100 (cool), null when unknown/unsupported
        public bool $supportsColorTemp = false,
    ) {}

    /**
     * @return array{provider: string, id: string, name: string, on: bool, brightness: int, color: string|null, reachable: bool, supports_color: bool, mode: string, color_temp: int|null, supports_color_temp: bool}
     */
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
            'mode' => $this->mode->value,
            'color_temp' => $this->colorTemp,
            'supports_color_temp' => $this->supportsColorTemp,
        ];
    }
}
