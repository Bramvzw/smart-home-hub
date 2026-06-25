<?php

namespace Modules\Deals\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Deals\Models\ProductListing;
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

    /**
     * @dataProvider priceProvider
     */
    public function test_price_parser_normalizes_and_rejects_garbage(mixed $raw, ?float $expected): void
    {
        config([
            'deals.amazon.enabled' => true,
            'deals.amazon.price_url' => 'https://example.com/price',
        ]);
        Http::fake(['https://example.com/price*' => Http::response(['price' => $raw])]);

        $listing = new ProductListing(['retailer' => 'amazon', 'external_id' => 'x']);

        $this->assertSame($expected, app(AmazonAdapter::class)->fetchPrice($listing));
    }

    public static function priceProvider(): array
    {
        return [
            'plain integer euros' => [319, 319.00],
            'plain float' => [49.99, 49.99],
            'european string with thousands' => ['€ 1.299,00', 1299.00],
            'us string with thousands' => ['1,299.00', 1299.00],
            'european decimal comma' => ['49,99', 49.99],
            'currency prefixed' => ['EUR 319', 319.00],
            'cents as minor units' => [['centAmount' => 29900], 299.00],
            'null is rejected' => [null, null],
            'empty string is rejected' => ['', null],
            'garbage text is rejected' => ['n/a', null],
            'dashes are rejected' => ['--', null],
            'zero is rejected' => [0, null],
            'negative is rejected' => [-5, null],
            'multi-dot garbage is rejected' => ['1.2.3.4', null],
        ];
    }

    private function fixture(string $file): string
    {
        return file_get_contents(__DIR__."/../Fixtures/{$file}");
    }
}
