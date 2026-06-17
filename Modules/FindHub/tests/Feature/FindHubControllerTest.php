<?php

namespace Modules\FindHub\Tests\Feature;

use Tests\TestCase;

class FindHubControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_index_links_to_the_configured_find_hub_url(): void
    {
        config(['findhub.url' => 'https://example.test/find']);

        $this->get(route('findhub.index'))
            ->assertStatus(200)
            ->assertSee('https://example.test/find', false)
            ->assertSee('Play sound');
    }
}
