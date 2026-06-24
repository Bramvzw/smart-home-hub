<?php

namespace Modules\Lighting\Data;

final readonly class LightPreset
{
    /**
     * @param  list<string>  $targetNameContains
     */
    public function __construct(
        public string $key,
        public string $label,
        public bool $power,
        public ?int $brightness = null,
        public ?string $color = null,
        public array $targetNameContains = [],
    ) {}

    public function targetsLight(Light $light): bool
    {
        if ($this->targetNameContains === []) {
            return true;
        }

        $name = strtolower($light->name);

        foreach ($this->targetNameContains as $target) {
            $target = strtolower(trim($target));

            if ($target !== '' && str_contains($name, $target)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{key: string, label: string, power: bool, brightness: int|null, color: string|null, target_name_contains: list<string>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'power' => $this->power,
            'brightness' => $this->brightness,
            'color' => $this->color,
            'target_name_contains' => $this->targetNameContains,
        ];
    }
}
