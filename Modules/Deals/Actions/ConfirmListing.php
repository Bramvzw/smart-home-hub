<?php

namespace Modules\Deals\Actions;

use Modules\Deals\Models\ProductListing;

class ConfirmListing
{
    public function __invoke(ProductListing $listing): ProductListing
    {
        $listing->update(['confirmed' => true, 'active' => true]);

        return $listing->fresh();
    }
}
