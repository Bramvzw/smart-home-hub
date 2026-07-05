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
     * Apply a fully known target state (presets) in as few provider calls as
     * possible — the target is known up front, so no state reads are needed.
     *
     * @param  int|null  $brightness  0-100
     * @param  string|null  $color  '#rrggbb'
     */
    public function applyState(string $id, bool $power, ?int $brightness, ?string $color): void;
}
