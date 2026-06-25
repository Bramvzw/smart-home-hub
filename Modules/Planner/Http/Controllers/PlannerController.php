<?php

namespace Modules\Planner\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Planner\Actions\AcceptAllPlanItems;
use Modules\Planner\Actions\AcceptPlanItem;
use Modules\Planner\Actions\GenerateWeeklyPlan;
use Modules\Planner\Actions\Intentions\CreateIntention;
use Modules\Planner\Actions\Intentions\DeleteIntention;
use Modules\Planner\Actions\Intentions\UpdateIntention;
use Modules\Planner\Actions\RejectPlanItem;
use Modules\Planner\Http\Resources\PlannerIntentionResource;
use Modules\Planner\Http\Resources\PlannerPlanItemResource;
use Modules\Planner\Http\Resources\PlannerPlanResource;
use Modules\Planner\Models\PlannerIntention;
use Modules\Planner\Models\PlannerPlan;
use Modules\Planner\Models\PlannerPlanItem;
use Modules\Planner\Services\Google\GoogleCalendarTokenService;
use Modules\Planner\View\ViewModels\PlannerViewModel;

class PlannerController
{
    public function __construct(private readonly PlannerViewModel $viewModel) {}

    public function index(Request $request): View|JsonResponse
    {
        $state = $this->viewModel->state();

        if ($request->expectsJson()) {
            return response()->json($state);
        }

        return view('planner::index', $state);
    }

    public function generate(Request $request, GenerateWeeklyPlan $generate): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['week_start' => 'nullable|date']);
        $plan = $generate(isset($data['week_start']) ? CarbonImmutable::parse($data['week_start']) : null, push: false);

        if (! $request->expectsJson()) {
            return redirect()->route('planner.index');
        }

        return response()->json(PlannerPlanResource::make($plan)->resolve($request));
    }

    public function acceptItem(PlannerPlanItem $item, AcceptPlanItem $accept): JsonResponse
    {
        return response()->json(PlannerPlanItemResource::make($accept($item)->load('intention'))->resolve());
    }

    public function acceptAll(Request $request, AcceptAllPlanItems $acceptAll): JsonResponse
    {
        $plan = PlannerPlan::query()->with('items')->latest('generated_at')->firstOrFail();

        return response()->json(PlannerPlanResource::make($acceptAll($plan)->load('items.intention'))->resolve($request));
    }

    public function rejectItem(PlannerPlanItem $item, RejectPlanItem $reject): JsonResponse
    {
        return response()->json(PlannerPlanItemResource::make($reject($item)->load('intention'))->resolve());
    }

    public function intentions(): JsonResponse
    {
        return response()->json(['intentions' => PlannerIntentionResource::collection(PlannerIntention::query()->orderBy('title')->get())->resolve()]);
    }

    public function storeIntention(Request $request, CreateIntention $create): JsonResponse
    {
        return response()->json(['intention' => PlannerIntentionResource::make($create($this->validatedIntention($request)))->resolve()], 201);
    }

    public function updateIntention(Request $request, PlannerIntention $intention, UpdateIntention $update): JsonResponse
    {
        return response()->json(['intention' => PlannerIntentionResource::make($update($intention, $this->validatedIntention($request, false)))->resolve()]);
    }

    public function destroyIntention(PlannerIntention $intention, DeleteIntention $delete): JsonResponse
    {
        $delete($intention);

        return response()->json(null, 204);
    }

    public function connect(GoogleCalendarTokenService $tokens): RedirectResponse
    {
        return redirect()->away($tokens->authorizationUrl());
    }

    public function callback(Request $request, GoogleCalendarTokenService $tokens): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_calendar_oauth_state', '');

        if ($request->filled('error')) {
            return redirect()->route('planner.index')
                ->with('error', 'Google Calendar koppelen is afgebroken: '.$request->query('error'));
        }

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $state)) {
            return redirect()->route('planner.index')
                ->with('error', 'Google Calendar koppelen mislukt: ongeldige state.');
        }

        if ($code === '') {
            return redirect()->route('planner.index')
                ->with('error', 'Google Calendar koppelen mislukt: geen autorisatiecode ontvangen.');
        }

        $tokens->exchangeCode($code);

        return redirect()->route('planner.index')
            ->with('success', 'Google Calendar gekoppeld.');
    }

    private function validatedIntention(Request $request, bool $create = true): array
    {
        return $request->validate([
            'title' => [$create ? 'required' : 'sometimes', 'string', 'max:160'],
            'category' => [$create ? 'required' : 'sometimes', 'in:sport,family,date,custom'],
            'frequency_type' => [$create ? 'required' : 'sometimes', 'in:times_per_week,weekly'],
            'target_min' => 'sometimes|integer|min:1|max:7',
            'target_max' => 'sometimes|integer|min:1|max:7',
            'preferred_windows' => 'nullable|array',
            'duration_minutes' => 'sometimes|integer|min:15|max:480',
            'location' => 'nullable|string|max:160',
            'active' => 'sometimes|boolean',
        ]);
    }
}
