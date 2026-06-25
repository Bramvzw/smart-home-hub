<?php

namespace Modules\Deals\Actions;

use App\Services\Ntfy\HubNotifier;
use Modules\Deals\Data\PriceCheckResult;
use Modules\Deals\Models\ProductListing;
use Modules\Deals\Services\PriceChecker;
use Throwable;

class CheckPrices
{
    public function __construct(
        private readonly PriceChecker $checker,
        private readonly HubNotifier $notifier,
    ) {}

    public function __invoke(): PriceCheckResult
    {
        $checked = 0;
        $drops = [];
        $failed = [];

        ProductListing::query()
            ->with('product')
            ->where('confirmed', true)
            ->where('active', true)
            ->orderBy('retailer')
            ->get()
            ->each(function (ProductListing $listing) use (&$checked, &$drops, &$failed): void {
                try {
                    $result = $this->checker->check($listing);
                } catch (Throwable) {
                    $failed[] = $listing->retailer;

                    return;
                }

                if (! $result) {
                    return;
                }

                $checked++;

                if ($result['dropped']) {
                    $drop = [
                        'product' => $listing->product?->name,
                        'listing_id' => $listing->id,
                        'retailer' => $listing->retailer,
                        'title' => $listing->title,
                        'url' => $listing->url,
                        'old_price' => $result['old_price'],
                        'new_price' => $result['new_price'],
                        'lowest_ever' => $result['lowest_ever'],
                    ];
                    $drops[] = $drop;
                    $this->notifyDrop($drop);
                }
            });

        return new PriceCheckResult($checked, $drops, array_values(array_unique($failed)));
    }

    private function notifyDrop(array $drop): void
    {
        $this->notifier->send(
            'Prijsdaling',
            sprintf(
                '%s goedkoper bij %s: €%.2f -> €%.2f (%s). %s',
                $drop['product'] ?? $drop['title'],
                $drop['retailer'],
                $drop['old_price'],
                $drop['new_price'],
                $drop['lowest_ever'] ? 'laagste ooit' : 'niet laagste ooit',
                $drop['url'],
            )
        );
    }
}
