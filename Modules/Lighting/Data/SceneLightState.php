<?php

namespace Modules\Lighting\Data;

use Modules\Lighting\Enums\LightMode;

/**
 * The stored state of one lamp in a user scene.
 */
final readonly class SceneLightState
{
    public function __construct(
        public string $provider,
        public string $id,
        public bool $power,
        public ?int $brightness = null,   // 0-100, null when off
        public LightMode $mode = LightMode::White,
        public ?string $color = null,     // '#rrggbb' in colour mode
        public ?int $colorTemp = null,    // 0 (warm) - 100 (cool) in white mode
    ) {}

    /**
     * Capture what a lamp shows right now. An off lamp only stores "off";
     * colour mode stores the colour, white mode the colour temperature (when
     * the lamp reports one).
     */
    public static function capture(Light $light): self
    {
        if (! $light->on) {
            return new self($light->provider, $light->id, false, mode: $light->mode);
        }

        if ($light->mode === LightMode::Colour && $light->color !== null) {
            return new self($light->provider, $light->id, true, $light->brightness, LightMode::Colour, strtolower($light->color));
        }

        return new self(
            $light->provider,
            $light->id,
            true,
            $light->brightness,
            LightMode::White,
            colorTemp: $light->supportsColorTemp ? $light->colorTemp : null,
        );
    }

    /**
     * The change set that brings a lamp into this state (group-write shape).
     *
     * @return array{power: bool, brightness?: int, color?: string, color_temp?: int}
     */
    public function changes(): array
    {
        if (! $this->power) {
            return ['power' => false];
        }

        $changes = ['power' => true];
        if ($this->brightness !== null) {
            $changes['brightness'] = $this->brightness;
        }
        if ($this->mode === LightMode::Colour && $this->color !== null) {
            $changes['color'] = $this->color;
        } elseif ($this->mode === LightMode::White && $this->colorTemp !== null) {
            $changes['color_temp'] = $this->colorTemp;
        }

        return $changes;
    }

    /** True when the lamp currently shows this state (active-scene detection). */
    public function matches(Light $light): bool
    {
        if (! $this->power) {
            return ! $light->on;
        }

        if (! $light->on) {
            return false;
        }

        if ($this->brightness !== null && $light->brightness !== $this->brightness) {
            return false;
        }

        if ($this->mode === LightMode::Colour && $this->color !== null) {
            return $light->mode === LightMode::Colour
                && strtolower(ltrim((string) $light->color, '#')) === strtolower(ltrim($this->color, '#'));
        }

        if ($this->colorTemp !== null) {
            return $light->mode === LightMode::White && $light->colorTemp === $this->colorTemp;
        }

        return true;
    }

    /**
     * @return array{provider: string, id: string, power: bool, brightness: int|null, mode: string, color: string|null, color_temp: int|null}
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'id' => $this->id,
            'power' => $this->power,
            'brightness' => $this->brightness,
            'mode' => $this->mode->value,
            'color' => $this->color,
            'color_temp' => $this->colorTemp,
        ];
    }
}
