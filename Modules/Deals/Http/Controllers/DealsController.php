<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Deals\Actions\AddWatchedProduct;
use Modules\Deals\Actions\CheckPrices;
use Modules\Deals\Actions\ConfirmListing;
use Modules\Deals\Actions\RemoveListing;
use Modules\Deals\Http\Resources\WatchedProductResource;
use Modules\Deals\Models\ProductListing;
use Modules\Deals\Models\WatchedProduct;
use Modules\Deals\View\ViewModels\DealsViewModel;

class DealsController
{
    public function __construct(private readonly DealsViewModel $viewModel) {}

    public function index(Request $request): View|JsonResponse
    {
        $state = $this->viewModel->state();

        if ($request->expectsJson()) {
            return response()->json($state);
        }

        return view('deals::index', $state);
    }

    public function storeProduct(Request $request, AddWatchedProduct $addWatchedProduct): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:160']);
        $product = $addWatchedProduct($data['name']);

        return response()->json([
            'product' => WatchedProductResource::make($product->load('listings'))->resolve($request),
        ], 201);
    }

    public function confirmListing(ProductListing $listing, ConfirmListing $confirmListing): JsonResponse
    {
        return response()->json([
            'listing' => $confirmListing($listing),
        ]);
    }

    public function destroyListing(ProductListing $listing, RemoveListing $removeListing): JsonResponse
    {
        $removeListing($listing);

        return response()->json(null, 204);
    }

    public function history(WatchedProduct $product): JsonResponse
    {
        $product->load('listings.pricePoints');

        return response()->json([
            'product_id' => $product->id,
            'listings' => $product->listings->map(fn (ProductListing $listing): array => [
                'id' => $listing->id,
                'retailer' => $listing->retailer,
                'title' => $listing->title,
                'price_points' => $listing->pricePoints->map(fn ($point): array => [
                    'price' => (float) $point->price,
                    'observed_at' => $point->observed_at->toIso8601String(),
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }

    public function check(CheckPrices $checkPrices): JsonResponse
    {
        return response()->json($checkPrices()->toArray());
    }
}
