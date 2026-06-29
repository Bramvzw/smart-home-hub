<?php

namespace Modules\Deals\Providers;

use App\Providers\ModuleServiceProvider;
use App\Support\Health\ModuleHealth;
use Illuminate\Support\Facades\Schema;
use Modules\Deals\Models\ProductListing;
use Modules\Deals\Services\PriceChecker;
use Modules\Deals\Services\ProductMatcher;
use Modules\Deals\Services\Retailers\AmazonAdapter;
use Modules\Deals\Services\Retailers\BolAdapter;
use Modules\Deals\Services\Retailers\TweakersAdapter;

class DealsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Deals';
    protected string $nameLower = 'deals';

    public function register(): void
    {
        parent::register();

        $this->app->tag([BolAdapter::class, AmazonAdapter::class, TweakersAdapter::class], 'deals.retailer');
        $this->app->bind(ProductMatcher::class, fn ($app) => new ProductMatcher($app->tagged('deals.retailer')));
        $this->app->bind(PriceChecker::class, fn ($app) => new PriceChecker($app->tagged('deals.retailer')));
    }

    public function getModuleName(): string
    {
        return 'Deals';
    }

    public function getModuleSlug(): string
    {
        return 'deals';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'Dealtracker', 'route' => 'deals.index', 'icon' => 'deals'],
        ];
    }

    public function health(): ModuleHealth
    {
        if (config('deals.bol.enabled') && (config('deals.bol.client_id') === '' || config('deals.bol.client_secret') === '')) {
            return ModuleHealth::degraded([
                'bol.com prijscheck uit — BOL_API_KEY en BOL_API_SECRET ontbreken',
            ]);
        }

        return ModuleHealth::ok();
    }

    public function getDashboardWidget(): ?string
    {
        if (! Schema::hasTable('product_listings')) {
            return null;
        }

        $tracked = ProductListing::query()->where('confirmed', true)->where('active', true)->count();

        return $tracked === 1 ? '1 tracked listing' : "{$tracked} tracked listings";
    }
}
