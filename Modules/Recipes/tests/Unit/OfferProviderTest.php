<?php

namespace Modules\Recipes\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Recipes\Services\AlbertHeijnOfferProvider;
use Modules\Recipes\Services\LidlOfferProvider;
use Tests\TestCase;

class OfferProviderTest extends TestCase
{
    public function test_albert_heijn_provider_parses_offers(): void
    {
        config([
            'recipes.sources.ah.anonymous_token_url' => 'https://example.com/ah-token',
            'recipes.sources.ah.offers_url' => 'https://example.com/ah-offers',
        ]);
        Http::fake([
            'https://example.com/ah-token' => Http::response(['access_token' => 'token']),
            'https://example.com/ah-offers*' => Http::response($this->fixture('ah-offers.json')),
        ]);

        $offers = app(AlbertHeijnOfferProvider::class)->fetch();

        $this->assertCount(1, $offers);
        $this->assertSame('ah', $offers[0]->store);
        $this->assertSame('ah-kipfilet', $offers[0]->externalId);
        $this->assertSame('Kipfilet', $offers[0]->productName);
        $this->assertSame(3.99, $offers[0]->offerPrice);
        $this->assertSame('35% korting', $offers[0]->discountLabel);
    }

    public function test_lidl_provider_parses_offers(): void
    {
        config(['recipes.sources.lidl.offers_url' => 'https://example.com/lidl-offers']);
        Http::fake([
            'https://example.com/lidl-offers' => Http::response($this->fixture('lidl-offers.json')),
        ]);

        $offers = app(LidlOfferProvider::class)->fetch();

        $this->assertCount(1, $offers);
        $this->assertSame('lidl', $offers[0]->store);
        $this->assertSame('lidl-paprika', $offers[0]->externalId);
        $this->assertSame('Paprika mix', $offers[0]->productName);
        $this->assertSame(1.79, $offers[0]->offerPrice);
        $this->assertSame('28% korting', $offers[0]->discountLabel);
    }

    private function fixture(string $file): string
    {
        return file_get_contents(__DIR__."/../Fixtures/{$file}");
    }
}
