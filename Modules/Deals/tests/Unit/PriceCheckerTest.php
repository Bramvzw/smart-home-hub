<?php

namespace Modules\Deals\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Deals\Contracts\RetailerAdapter;
use Modules\Deals\Data\ListingCandidate;
use Modules\Deals\Models\ProductListing;
use Modules\Deals\Models\WatchedProduct;
use Modules\Deals\Services\PriceChecker;
use Tests\TestCase;

class PriceCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_checker_detects_drop_and_updates_lowest_price(): void
    {
        $product = WatchedProduct::query()->create(['name' => 'Bambu Lab AMS', 'query' => 'bambu lab ams']);
        $listing = $product->listings()->create([
            'retailer' => 'fake',
            'external_id' => 'fake-1',
            'title' => 'Bambu Lab AMS',
            'url' => 'https://example.com',
            'current_price' => 319,
            'lowest_price' => 309,
            'confirmed' => true,
        ]);

        $result = (new PriceChecker([new FakeDealsRetailer(299)]))->check($listing);

        $this->assertTrue($result['dropped']);
        $this->assertSame(319.0, $result['old_price']);
        $this->assertSame(299.0, $result['new_price']);
        $this->assertTrue($result['lowest_ever']);
        $this->assertDatabaseHas('price_points', [
            'product_listing_id' => $listing->id,
            'price' => 299,
        ]);
        $this->assertSame('299.00', $listing->fresh()->lowest_price);
    }

    public function test_equal_or_higher_price_is_not_a_drop(): void
    {
        $product = WatchedProduct::query()->create(['name' => 'Bambu Lab AMS', 'query' => 'bambu lab ams']);
        $listing = $product->listings()->create([
            'retailer' => 'fake',
            'external_id' => 'fake-1',
            'title' => 'Bambu Lab AMS',
            'url' => 'https://example.com',
            'current_price' => 319,
            'lowest_price' => 299,
            'confirmed' => true,
        ]);

        $result = (new PriceChecker([new FakeDealsRetailer(329)]))->check($listing);

        $this->assertFalse($result['dropped']);
        $this->assertSame('299.00', $listing->fresh()->lowest_price);
    }

    public function test_garbage_price_resolving_to_null_never_registers_a_drop(): void
    {
        $product = WatchedProduct::query()->create(['name' => 'Bambu Lab AMS', 'query' => 'bambu lab ams']);
        $listing = $product->listings()->create([
            'retailer' => 'fake',
            'external_id' => 'fake-1',
            'title' => 'Bambu Lab AMS',
            'url' => 'https://example.com',
            'current_price' => 319,
            'lowest_price' => 309,
            'confirmed' => true,
        ]);

        // A garbage/missing retailer price is parsed to null upstream; the
        // checker must skip it entirely rather than treat it as a 0 drop.
        $result = (new PriceChecker([new FakeDealsRetailer(null)]))->check($listing);

        $this->assertNull($result);
        $this->assertSame('319.00', $listing->fresh()->current_price);
        $this->assertSame('309.00', $listing->fresh()->lowest_price);
        $this->assertDatabaseMissing('price_points', ['product_listing_id' => $listing->id]);
    }
}

class FakeDealsRetailer implements RetailerAdapter
{
    public function __construct(private readonly ?float $price) {}

    public function retailer(): string
    {
        return 'fake';
    }

    public function search(string $query): array
    {
        return [new ListingCandidate('fake', 'fake-1', 'Fake', 'https://example.com', $this->price)];
    }

    public function fetchPrice(ProductListing $listing): ?float
    {
        return $this->price;
    }
}
