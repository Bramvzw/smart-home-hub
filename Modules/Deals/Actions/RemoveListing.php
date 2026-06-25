<?php

namespace Modules\Deals\Actions;

use Modules\Deals\Models\ProductListing;

class RemoveListing
{
    public function __invoke(ProductListing $listing): void
    {
        $listing->delete();
    }
}
