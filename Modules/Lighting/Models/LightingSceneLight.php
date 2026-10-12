<?php

namespace Modules\Lighting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Lighting\Data\SceneLightState;
use Modules\Lighting\Enums\LightMode;

/**
 * @property int $id
 * @property int $lighting_scene_id
 * @property string $provider
 * @property string $light_id
 * @property bool $power
 * @property int|null $brightness
 * @property LightMode $mode
 * @property string|null $color
 * @property int|null $color_temp
 */
class LightingSceneLight extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'lighting_scene_id',
        'provider',
        'light_id',
        'power',
        'brightness',
        'mode',
        'color',
        'color_temp',
    ];

    /** @return BelongsTo<LightingScene, $this> */
    public function scene(): BelongsTo
    {
        return $this->belongsTo(LightingScene::class, 'lighting_scene_id');
    }

    public function toState(): SceneLightState
    {
        return new SceneLightState(
            provider: $this->provider,
            id: $this->light_id,
            power: $this->power,
            brightness: $this->brightness,
            mode: $this->mode,
            color: $this->color,
            colorTemp: $this->color_temp,
        );
    }

    /**
     * @return array{provider: string, light_id: string, power: bool, brightness: int|null, mode: string, color: string|null, color_temp: int|null}
     */
    public static function attributesFrom(SceneLightState $state): array
    {
        return [
            'provider' => $state->provider,
            'light_id' => $state->id,
            'power' => $state->power,
            'brightness' => $state->brightness,
            'mode' => $state->mode->value,
            'color' => $state->color,
            'color_temp' => $state->colorTemp,
        ];
    }

    protected function casts(): array
    {
        return [
            'power' => 'boolean',
            'brightness' => 'integer',
            'mode' => LightMode::class,
            'color_temp' => 'integer',
        ];
    }
}
