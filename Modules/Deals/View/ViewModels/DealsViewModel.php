<?php

namespace Modules\Deals\View\ViewModels;

use Modules\Deals\Http\Resources\WatchedProductResource;
use Modules\Deals\Models\WatchedProduct;

class DealsViewModel
{
    public function state(): array
    {
        return [
            'products' => WatchedProductResource::collection(
                WatchedProduct::query()->with('listings')->orderByDesc('created_at')->get()
            )->resolve(),
        ];
    }
}
