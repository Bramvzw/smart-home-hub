<?php

namespace Modules\Lighting\Contracts;

use Modules\Lighting\Data\Light;

interface LightProvider
{
    /** Stable provider key, e.g. 'tuya' or 'govee'. */
    public function key(): string;

    /** Human label for grouping in the UI. */
    public function label(): string;

    /** True when the provider has the credentials it needs. */
    public function isConfigured(): bool;

    /**
     * All lights for this provider, normalised to the shared Light DTO.
     * Throws when the provider as a whole is unreachable.
     *
     * @return list<Light>
     */
    public function lights(): array;

    public function setPower(string $id, bool $on): void;

    /** @param  int  $percent  0-100 */
    public function setBrightness(string $id, int $percent): void;

    /** @param  string  $hex  '#rrggbb' */
    public function setColor(string $id, string $hex): void;

    /**
     * Switch to the white LEDs at the given colour temperature.
     *
     * @param  int  $percent  0 (warmest) - 100 (coolest)
     */
    public function setColorTemp(string $id, int $percent): void;

    /**
     * Apply a fully known target state (presets) in as few provider calls as
     * possible — the target is known up front, so no state reads are needed.
     *
     * When both a colour and a colour temperature are given, white mode wins.
     *
     * @param  int|null  $brightness  0-100
     * @param  string|null  $color  '#rrggbb'
     * @param  int|null  $colorTemp  0 (warm) - 100 (cool)
     */
    public function applyState(string $id, bool $power, ?int $brightness, ?string $color, ?int $colorTemp = null): void;

    /**
     * Apply a partial change set to one light (single-light writes). The
     * cached Light snapshot carries the current mode, colour and brightness,
     * so no status read is needed; providers that accept batches send one
     * call. A missing `power` leaves the switch as it is.
     *
     * @param  array{power?: bool, brightness?: int, color?: string, color_temp?: int}  $changes
     */
    public function applyChanges(Light $light, array $changes): void;

    /**
     * Apply one change set to several of this provider's lights (group
     * writes). The cached Light snapshots carry the current mode, colour and
     * brightness, so providers can build full commands without status reads.
     * A missing `power` means "on". Failures are isolated per light.
     *
     * @param  list<Light>  $lights
     * @param  array{power?: bool, brightness?: int, color?: string, color_temp?: int}  $changes
     * @return list<string> ids of the lights that could not be updated
     */
    public function applyToLights(array $lights, array $changes): array;
}
