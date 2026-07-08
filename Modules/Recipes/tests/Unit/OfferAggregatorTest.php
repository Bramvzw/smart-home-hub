<?php

namespace Modules\Recipes\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Recipes\Contracts\OfferProvider;
use Modules\Recipes\Data\OfferData;
use Modules\Recipes\Services\OfferAggregator;
use RuntimeException;
use Tests\TestCase;

class OfferAggregatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_aggregator_upserts_offers_and_records_failing_stores(): void
    {
        $provider = new class implements OfferProvider
        {
            public function store(): string
            {
                return 'ah';
            }

            public function fetch(): array
            {
                return [
                    new OfferData('ah', 'same-id', 'Kipfilet', offerPrice: 3.99),
                    new OfferData('ah', 'same-id', 'Kipfilet', offerPrice: 3.49),
                ];
            }
        };
        $failing = new class implements OfferProvider
        {
            public function store(): string
            {
                return 'lidl';
            }

            public function fetch(): array
            {
                throw new RuntimeException('broken');
            }
        };

        $result = (new OfferAggregator([$provider, $failing]))->fetch(CarbonImmutable::parse('2026-06-26'));

        $this->assertSame('2026-W26', $result->weekKey);
        $this->assertSame(['ah'], $result->storesFetched);
        $this->assertSame(['lidl'], $result->storesFailed);
        $this->assertDatabaseCount('grocery_offers', 1);
        $this->assertDatabaseHas('grocery_offers', [
            'store' => 'ah',
            'external_id' => 'same-id',
            'offer_price' => 3.49,
        ]);
        $this->assertDatabaseHas('recipe_runs', [
            'week_key' => '2026-W26',
        ]);
    }

    public function test_aggregator_skips_stores_outside_the_configured_list(): void
    {
        config(['recipes.stores' => ['ah']]);

        $skipped = new class implements OfferProvider
        {
            public bool $fetched = false;

            public function store(): string
            {
                return 'lidl';
            }

            public function fetch(): array
            {
                $this->fetched = true;

                return [];
            }
        };

        $result = (new OfferAggregator([$skipped]))->fetch(CarbonImmutable::parse('2026-06-26'));

        $this->assertFalse($skipped->fetched);
        $this->assertSame([], $result->storesFetched);
        $this->assertSame([], $result->storesFailed);
    }
}
