<?php

namespace Modules\Lighting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Modules\Lighting\Data\SceneLightState;
use Modules\Lighting\Models\Builders\LightingSceneBuilder;

/**
 * A user-made lighting scene: a stored state per lamp. Lamps that are not in
 * the scene are left untouched when it is applied.
 *
 * @property int $id
 * @property string $name
 * @property string $accent
 * @property int $sort_order
 */
class LightingScene extends Model
{
    protected $fillable = [
        'name',
        'accent',
        'sort_order',
    ];

    public function newEloquentBuilder($query): LightingSceneBuilder
    {
        /** @var BaseBuilder $query */
        return new LightingSceneBuilder($query);
    }

    /** @return HasMany<LightingSceneLight, $this> */
    public function lights(): HasMany
    {
        return $this->hasMany(LightingSceneLight::class)->orderBy('id');
    }

    /**
     * @return list<SceneLightState>
     */
    public function states(): array
    {
        return array_values($this->lights->map(static fn (LightingSceneLight $light): SceneLightState => $light->toState())->all());
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
