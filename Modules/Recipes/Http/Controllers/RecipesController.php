<?php

namespace Modules\Recipes\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Recipes\Actions\GenerateRecipes;
use Modules\Recipes\Http\Resources\OfferResource;
use Modules\Recipes\Http\Resources\RecipeResource;
use Modules\Recipes\Models\GroceryOffer;
use Modules\Recipes\Models\Recipe;
use Modules\Recipes\Services\OfferAggregator;
use Modules\Recipes\View\ViewModels\RecipesViewModel;

class RecipesController
{
    public function __construct(
        private readonly RecipesViewModel $viewModel,
        private readonly OfferAggregator $offerAggregator,
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        $state = $this->viewModel->state($request->query('week'));

        if ($request->expectsJson()) {
            return response()->json($state);
        }

        return view('recipes::index', $state);
    }

    public function show(Recipe $recipe): JsonResponse
    {
        return response()->json(RecipeResource::make($recipe)->resolve());
    }

    public function offers(Request $request): JsonResponse
    {
        $weekKey = $request->query('week') ?: $this->offerAggregator->weekKey();

        return response()->json([
            'week_key' => $weekKey,
            'offers' => OfferResource::collection(
                GroceryOffer::query()
                    ->where('week_key', $weekKey)
                    ->orderBy('store')
                    ->orderBy('product_name')
                    ->get()
            )->resolve(),
        ]);
    }

    public function generate(Request $request, GenerateRecipes $generateRecipes): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'week_key' => 'nullable|string|max:12',
            'refetch' => 'sometimes|boolean',
        ]);

        $generateRecipes(
            weekKey: $data['week_key'] ?? null,
            push: true,
            refetchOffers: (bool) ($data['refetch'] ?? false),
        );

        if (! $request->expectsJson()) {
            return redirect()->route('recipes.index');
        }

        return response()->json($this->viewModel->state($data['week_key'] ?? null));
    }
}
