<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModuleHealthBannerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_banner_shows_with_concrete_issues_when_a_module_needs_setup(): void
    {
        config(['calendar.feeds' => []]);

        $this->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('configuratie nodig')
            ->assertSee('CALENDAR_ICS_FEEDS');
    }

    public function test_banner_is_absent_when_a_module_is_ready(): void
    {
        $feed = 'https://example.test/secret/basic.ics';
        config(['calendar.feeds' => [['label' => 'Work', 'color' => '#f2ad66', 'url' => $feed]]]);
        Http::fake([$feed => Http::response("BEGIN:VCALENDAR\nVERSION:2.0\nEND:VCALENDAR", 200)]);

        $this->get(route('calendar.index'))
            ->assertOk()
            ->assertDontSee('configuratie nodig');
    }
}
