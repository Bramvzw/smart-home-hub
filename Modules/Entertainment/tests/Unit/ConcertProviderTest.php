<?php

namespace Modules\Entertainment\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Entertainment\Services\Concerts\TicketmasterProvider;
use Tests\TestCase;

class ConcertProviderTest extends TestCase
{
    public function test_ticketmaster_provider_parses_events(): void
    {
        config(['entertainment.concerts.ticketmaster_key' => 'key']);
        Http::fake([
            'https://app.ticketmaster.com/discovery/v2/events.json*' => Http::response([
                '_embedded' => [
                    'events' => [[
                        'id' => 'tm-1',
                        'name' => 'The National',
                        'url' => 'https://example.com/the-national',
                        'dates' => ['start' => ['dateTime' => '2026-07-01T19:00:00Z']],
                        '_embedded' => ['venues' => [['name' => 'Ziggo Dome', 'city' => ['name' => 'Amsterdam']]]],
                    ]],
                ],
            ]),
        ]);

        $concerts = app(TicketmasterProvider::class)->fetch();

        $this->assertCount(1, $concerts);
        $this->assertSame('ticketmaster', $concerts[0]->source);
        $this->assertSame('The National', $concerts[0]->artist);
        $this->assertSame('Ziggo Dome', $concerts[0]->venue);
    }
}
