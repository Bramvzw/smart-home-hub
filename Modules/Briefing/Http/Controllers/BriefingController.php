<?php

namespace Modules\Briefing\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Briefing\Actions\GenerateBriefing;
use Modules\Briefing\Http\Resources\BriefingResource;
use Modules\Briefing\View\ViewModels\BriefingViewModel;

class BriefingController
{
    public function __construct(
        private readonly BriefingViewModel $viewModel,
    ) {}

    public function index(Request $request): View|JsonResponse|BriefingResource
    {
        $state = $this->viewModel->today();

        if ($request->expectsJson()) {
            if (! $state['hasBriefing']) {
                return response()->json([
                    'date' => $state['date'],
                    'message' => 'No briefing generated today.',
                ], 404);
            }

            return BriefingResource::make($state['briefing']);
        }

        return view('briefing::index', $state);
    }

    public function regenerate(Request $request, GenerateBriefing $generateBriefing): JsonResponse|RedirectResponse
    {
        $briefing = $generateBriefing(
            date: CarbonImmutable::now((string) config('briefing.timezone', 'Europe/Amsterdam')),
            push: false,
        );

        if (! $request->expectsJson()) {
            return redirect()->route('briefing.index');
        }

        return response()->json(BriefingResource::make($briefing)->resolve());
    }
}
