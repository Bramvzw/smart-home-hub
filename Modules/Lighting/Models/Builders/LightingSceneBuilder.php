<?php

namespace Modules\Lighting\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Modules\Lighting\Models\LightingScene;

/**
 * @extends Builder<LightingScene>
 */
class LightingSceneBuilder extends Builder
{
    /** Scene-strip order: sort_order, then creation order. */
    public function ordered(): static
    {
        $this->orderBy('sort_order')->orderBy('id');

        return $this;
    }
}
