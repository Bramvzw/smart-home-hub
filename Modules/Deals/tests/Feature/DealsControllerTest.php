<?php

namespace Modules\Deals\Tests\Feature;

use App\Services\Ntfy\HubNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Deals\Contracts\RetailerAdapter;
use Modules\Deals\Data\ListingCandidate;
use Modules\Deals\Models\ProductListing;
use Modules\Deals\Models\WatchedProduct;
use Modules\Deals\Services\PriceChecker;
use Modules\Deals\Services\ProductMatcher;
use Tests\TestCase;

class DealsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_product_creates_unconfirmed_candidate_listings(): void
    {
        $this->app->instance(ProductMatcher::class, new ProductMatcher([
            new FakeDealsControllerRetailer('bol', 319),
            new FailingSearchRetailer,
        ]));

        $response = $this->postJson(route('deals.products.store'), ['name' => 'Bambu Lab AMS']);

        $response->assertCreated()
            ->assertJsonPath('product.name', 'Bambu Lab AMS')
            ->assertJsonPath('product.listings.0.confirmed', false)
            ->assertJsonPath('product.listings.0.retailer', 'bol');
        $this->assertDatabaseHas('watched_products', ['query' => 'bambu lab ams']);
        $this->assertDatabaseHas('product_listings', ['retailer' => 'bol', 'confirmed' => false]);
    }

    public function test_confirm_and_remove_listing(): void
    {
        $listing = $this->listing();

        $this->postJson(route('deals.listings.confirm', $listing))
            ->assertOk()
            ->assertJsonPath('listing.confirmed', true);
        $this->assertTrue($listing->fresh()->confirmed);

        $this->deleteJson(route('deals.listings.destroy', $listing))->assertNoContent();
        $this->assertDatabaseMissing('product_listings', ['id' => $listing->id]);
    }

    public function test_check_prices_sends_one_ntfy_per_drop(): void
    {
        $listing = $this->listing(['confirmed' => true, 'current_price' => 319, 'lowest_price' => 309]);
        $notifier = new FakeDealsNotifier;
        $this->app->instance(HubNotifier::class, $notifier);
        $this->app->instance(PriceChecker::class, new PriceChecker([new FakeDealsControllerRetailer('bol', 299)]));

        $this->postJson(route('deals.check'))
            ->assertOk()
            ->assertJsonPath('checked', 1)
            ->assertJsonPath('drops.0.listing_id', $listing->id)
            ->assertJsonPath('drops.0.lowest_ever', true);

        $this->assertCount(1, $notifier->sent);
        $this->assertStringContainsString('€319.00 -> €299.00', $notifier->sent[0]['message']);
        $this->assertStringContainsString('https://example.com', $notifier->sent[0]['message']);
    }

    public function test_index_renders_watchlist_html(): void
    {
        $this->withoutVite();
        $this->listing(['confirmed' => true, 'current_price' => 319, 'lowest_price' => 299]);

        $this->get(route('deals.index'))
            ->assertOk()
            ->assertSee('Dealtracker')
            ->assertSee('Bambu Lab AMS');
    }

    public function test_index_and_history_contracts(): void
    {
        $listing = $this->listing(['confirmed' => true, 'current_price' => 319, 'lowest_price' => 299]);
        $listing->pricePoints()->create(['price' => 319, 'observed_at' => now()]);

        $this->getJson(route('deals.index'))
            ->assertOk()
            ->assertJsonPath('products.0.name', 'Bambu Lab AMS')
            ->assertJsonPath('products.0.listings.0.current_price', 319)
            ->assertJsonStructure(['products' => [['id', 'name', 'listings' => [['retailer', 'title', 'url', 'current_price', 'lowest_price', 'confirmed', 'last_checked_at']]]]]);

        $this->getJson(route('deals.products.history', $listing->product))
            ->assertOk()
            ->assertJsonPath('listings.0.price_points.0.price', 319);
    }

    private function listing(array $overrides = []): ProductListing
    {
        $product = WatchedProduct::query()->create(['name' => 'Bambu Lab AMS', 'query' => 'bambu lab ams']);

        return $product->listings()->create(array_merge([
            'retailer' => 'bol',
            'external_id' => 'bol-ams',
            'title' => 'Bambu Lab AMS',
            'url' => 'https://example.com',
            'confirmed' => false,
            'active' => true,
        ], $overrides));
    }
}

class FakeDealsControllerRetailer implements RetailerAdapter
{
    public function __construct(private readonly string $retailer, private readonly float $price) {}

    public function retailer(): string
    {
        return $this->retailer;
    }

    public function search(string $query): array
    {
        return [new ListingCandidate($this->retailer, $this->retailer.'-ams', 'Bambu Lab AMS', 'https://example.com', $this->price)];
    }

    public function fetchPrice(ProductListing $listing): ?float
    {
        return $this->price;
    }
}

class FailingSearchRetailer implements RetailerAdapter
{
    public function retailer(): string
    {
        return 'amazon';
    }

    public function search(string $query): array
    {
        throw new \RuntimeException('failed');
    }

    public function fetchPrice(ProductListing $listing): ?float
    {
        return null;
    }
}

class FakeDealsNotifier extends HubNotifier
{
    public array $sent = [];

    public function __construct()
    {
        parent::__construct('https://ntfy.sh', 'topic', '', 10);
    }

    public function send(string $title, string $message): void
    {
        $this->sent[] = compact('title', 'message');
    }
}
