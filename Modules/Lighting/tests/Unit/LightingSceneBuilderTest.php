<?php

namespace Modules\Lighting\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Lighting\Models\LightingScene;
use Tests\TestCase;

class LightingSceneBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordered_sorts_by_sort_order_then_creation(): void
    {
        foreach ([['Late', 3], ['First', 1], ['Second', 2], ['Also second', 2]] as [$name, $order]) {
            LightingScene::query()->create(['name' => $name, 'accent' => '#e0a44e', 'sort_order' => $order]);
        }

        $this->assertSame(
            ['First', 'Second', 'Also second', 'Late'],
            LightingScene::query()->ordered()->pluck('name')->all(),
        );
    }
}
