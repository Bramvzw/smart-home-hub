<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Models\GoogleCalendarToken;
use Tests\TestCase;

class ModuleHealthBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_banner_shows_with_concrete_issues_when_a_module_needs_setup(): void
    {
        config([
            'calendar.google.client_id' => '',
            'calendar.google.client_secret' => '',
            'calendar.google.redirect' => '',
        ]);

        $this->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('needs configuration')
            ->assertSee('GOOGLE_CLIENT_ID');
    }

    public function test_banner_is_absent_when_a_module_is_ready(): void
    {
        config([
            'calendar.google.client_id' => 'id',
            'calendar.google.client_secret' => 'secret',
            'calendar.google.redirect' => 'https://hub.test/calendar/google/callback',
            'ai.anthropic.api_key' => 'sk-present',
        ]);
        GoogleCalendarToken::query()->create([
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => CarbonImmutable::now()->addHour(),
        ]);
        Http::fake(['https://www.googleapis.com/calendar/v3/calendars/*' => Http::response(['items' => []], 200)]);

        $this->get(route('calendar.index'))
            ->assertOk()
            ->assertDontSee('needs configuration');
    }
}
