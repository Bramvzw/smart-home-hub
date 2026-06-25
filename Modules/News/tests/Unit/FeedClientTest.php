<?php

namespace Modules\News\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\News\Exceptions\FeedUnavailable;
use Modules\News\Services\FeedClient;
use Tests\TestCase;

class FeedClientTest extends TestCase
{
    public function test_it_parses_rss_items_and_normalizes_fields(): void
    {
        Http::fake([
            'https://example.com/rss' => Http::response($this->fixture('rss.xml'), 200, [
                'Content-Type' => 'application/rss+xml',
            ]),
        ]);

        $items = app(FeedClient::class)->fetch('https://example.com/rss');

        $this->assertCount(2, $items);
        $this->assertSame('rss-guid-1', $items[0]->guid);
        $this->assertSame('Bambu Firmware Released', $items[0]->title);
        $this->assertSame('Printer update & notes.', $items[0]->summary);
        $this->assertSame('https://example.com/image.jpg', $items[0]->imageUrl);
        $this->assertSame('https://example.com/no-guid', $items[1]->guid);
    }

    public function test_it_parses_atom_items(): void
    {
        Http::fake([
            'https://example.com/atom' => Http::response($this->fixture('atom.xml'), 200, [
                'Content-Type' => 'application/atom+xml',
            ]),
        ]);

        $items = app(FeedClient::class)->fetch('https://example.com/atom');

        $this->assertCount(1, $items);
        $this->assertSame('tag:example.com,2026:atom-entry', $items[0]->guid);
        $this->assertSame('Laravel Atom Entry', $items[0]->title);
        $this->assertSame('Atom summary for PHP 8', $items[0]->summary);
        $this->assertSame('Dev Author', $items[0]->author);
    }

    public function test_it_throws_on_non_successful_response(): void
    {
        Http::fake([
            'https://example.com/missing' => Http::response('Not found', 404),
        ]);

        $this->expectException(FeedUnavailable::class);

        app(FeedClient::class)->fetch('https://example.com/missing');
    }

    public function test_it_throws_on_malformed_feed_body(): void
    {
        Http::fake([
            'https://example.com/bad' => Http::response('<rss><channel><item></channel></rss>', 200),
        ]);

        $this->expectException(FeedUnavailable::class);

        app(FeedClient::class)->fetch('https://example.com/bad');
    }

    private function fixture(string $file): string
    {
        return file_get_contents(__DIR__."/../Fixtures/{$file}");
    }
}
