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
     * This light after a successful write of `$changes`. Fields the lamp
     * cannot show (colour / colour temperature) are ignored; a colour
     * temperature wins over a colour, like the providers do.
     *
     * @param  array{power?: bool, brightness?: int, color?: string, color_temp?: int}  $changes
     */
    public function withChanges(array $changes): self
    {
        $mode = $this->mode;
        $color = $this->color;
        $colorTemp = $this->colorTemp;

        if (isset($changes['color_temp']) && $this->supportsColorTemp) {
            $mode = LightMode::White;
            $colorTemp = max(0, min(100, $changes['color_temp']));
        } elseif (isset($changes['color']) && $this->supportsColor) {
            $mode = LightMode::Colour;
            $color = strtolower($changes['color']);
        }

        return new self(
            provider: $this->provider,
            id: $this->id,
            name: $this->name,
            on: $changes['power'] ?? $this->on,
            brightness: isset($changes['brightness']) ? max(0, min(100, $changes['brightness'])) : $this->brightness,
            color: $color,
            reachable: $this->reachable,
            supportsColor: $this->supportsColor,
            mode: $mode,
            colorTemp: $colorTemp,
            supportsColorTemp: $this->supportsColorTemp,
        );
    }

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
