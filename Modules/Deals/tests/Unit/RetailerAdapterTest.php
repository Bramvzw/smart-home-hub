<?php

namespace Modules\Deals\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Deals\Services\Retailers\AmazonAdapter;
use Tests\TestCase;

class RetailerAdapterTest extends TestCase
{
    public function test_adapter_parses_listing_candidates(): void
    {
        config([
            'deals.amazon.enabled' => true,
            'deals.amazon.search_url' => 'https://example.com/search',
        ]);
        Http::fake(['https://example.com/search*' => Http::response($this->fixture('listings.json'))]);

        $candidates = app(AmazonAdapter::class)->search('Bambu Lab AMS');

        $this->assertCount(1, $candidates);
        $this->assertSame('amazon', $candidates[0]->retailer);
        $this->assertSame('bol-ams', $candidates[0]->externalId);
        $this->assertSame('Bambu Lab AMS', $candidates[0]->title);
        $this->assertSame(319.00, $candidates[0]->price);
    }

    private function fixture(string $file): string
    {
        return file_get_contents(__DIR__."/../Fixtures/{$file}");
    }
}
