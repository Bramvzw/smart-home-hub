<?php

namespace Modules\Deals\Briefing;

use App\Contracts\BriefingSource;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Modules\Deals\Models\ProductListing;

class DealsBriefingSource implements BriefingSource
{
    public function key(): string
    {
        return 'deals';
    }

    public function label(): string
    {
        return 'Dealtracker';
    }

    public function priority(): int
    {
        return 60;
    }

    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        $drops = ProductListing::query()
            ->with(['product', 'pricePoints'])
            ->where('confirmed', true)
            ->where('active', true)
            ->whereNotNull('current_price')
            ->whereNotNull('lowest_price')
            ->whereColumn('current_price', '<=', 'lowest_price')
            ->whereDate('last_checked_at', $date)
            ->has('pricePoints', '>=', 2)
            ->orderBy('retailer')
            ->get();

        if ($drops->isEmpty()) {
            return null;
        }

        $labels = $drops->map(fn (ProductListing $listing): string => sprintf(
            '%s (€%s)',
            $listing->product?->name ?? $listing->title,
            number_format((float) $listing->current_price, 2, ',', '.'),
        ));

        return new BriefingSection(
            key: $this->key(),
            label: $this->label(),
            priority: $this->priority(),
            summary: $drops->count().' price drop'.($drops->count() === 1 ? '' : 's').' today: '.$labels->implode(', '),
            data: [
                'drops' => $drops->map(fn (ProductListing $listing): array => [
                    'product' => $listing->product?->name ?? $listing->title,
                    'retailer' => $listing->retailer,
                    'title' => $listing->title,
                    'url' => $listing->url,
                    'current_price' => (float) $listing->current_price,
                    'lowest_price' => (float) $listing->lowest_price,
                    'previous_price' => $this->previousPrice($listing),
                ])->values()->all(),
            ],
        );
    }

    private function previousPrice(ProductListing $listing): ?float
    {
        $previous = $listing->pricePoints->reverse()->skip(1)->first();

        return $previous !== null ? (float) $previous->price : null;
    }
}
