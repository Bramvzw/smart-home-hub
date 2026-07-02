<?php

namespace Modules\Calendar\Http\Controllers;

use App\Contracts\SchedulableGoals;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Calendar\Actions\AcceptAllPlanItems;
use Modules\Calendar\Actions\AcceptPlanItem;
use Modules\Calendar\Actions\GenerateWeeklyPlan;
use Modules\Calendar\Actions\RejectPlanItem;
use Modules\Calendar\Http\Resources\CalendarPlanItemResource;
use Modules\Calendar\Http\Resources\CalendarPlanResource;
use Modules\Calendar\Http\Resources\GoalResource;
use Modules\Calendar\Http\Resources\HabitResource;
use Modules\Calendar\Models\CalendarPlan;
use Modules\Calendar\Models\CalendarPlanItem;
use Modules\Calendar\Services\Google\GoogleCalendarTokenService;
use Modules\Calendar\View\ViewModels\CalendarViewModel;

class CalendarController
{
    public function __construct(
        private readonly CalendarViewModel $viewModel,
        private readonly SchedulableGoals $goals,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $state = $this->viewModel->page();

        if ($request->expectsJson()) {
            return response()->json($state);
        }

        return view('calendar::index', $state);
    }

    public function generate(Request $request, GenerateWeeklyPlan $generate): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['week_start' => 'nullable|date']);
        $plan = $generate(isset($data['week_start']) ? CarbonImmutable::parse($data['week_start']) : null, push: false);

        if (! $request->expectsJson()) {
            return redirect()->route('calendar.index');
        }

        return response()->json(CalendarPlanResource::make($plan)->resolve($request));
    }

    public function acceptItem(CalendarPlanItem $item, AcceptPlanItem $accept): JsonResponse
    {
        return response()->json(CalendarPlanItemResource::make($accept($item))->resolve());
    }

    public function acceptAll(Request $request, AcceptAllPlanItems $acceptAll): JsonResponse
    {
        $plan = CalendarPlan::latestGenerated()->firstOrFail();

        return response()->json(CalendarPlanResource::make($acceptAll($plan)->load('items'))->resolve($request));
    }

    public function rejectItem(CalendarPlanItem $item, RejectPlanItem $reject): JsonResponse
    {
        return response()->json(CalendarPlanItemResource::make($reject($item))->resolve());
    }

    public function goals(): JsonResponse
    {
        return response()->json([
            'goals' => GoalResource::collection($this->goals->all())->resolve(),
        ]);
    }

    public function storeGoal(Request $request): JsonResponse
    {
        $goal = $this->goals->create($this->validatedGoal($request) + ['plannable' => $request->boolean('plannable', true)]);

        return response()->json(['goal' => GoalResource::make($goal)->resolve()], 201);
    }

    public function updateGoal(Request $request, int $goal): JsonResponse
    {
        $updated = $this->goals->update($goal, $this->validatedGoal($request, false));

        return response()->json(['goal' => GoalResource::make($updated)->resolve()]);
    }

    public function destroyGoal(int $goal): JsonResponse
    {
        $this->goals->delete($goal);

        return response()->json(null, 204);
    }

    public function completeHabit(Request $request, int $habit): JsonResponse
    {
        return response()->json(['habit' => HabitResource::make($this->goals->complete($habit, $this->date($request)))->resolve()]);
    }

    public function undoHabit(Request $request, int $habit): JsonResponse
    {
        return response()->json(['habit' => HabitResource::make($this->goals->undo($habit, $this->date($request)))->resolve()]);
    }

    private function date(Request $request): CarbonImmutable
    {
        $date = $request->input('date');

        return $date
            ? CarbonImmutable::parse((string) $date)->startOfDay()
            : CarbonImmutable::now((string) config('app.timezone', 'UTC'))->startOfDay();
    }

    public function connect(GoogleCalendarTokenService $tokens): RedirectResponse
    {
        return redirect()->away($tokens->authorizationUrl());
    }

    public function callback(Request $request, GoogleCalendarTokenService $tokens): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_calendar_oauth_state', '');

        if ($request->filled('error')) {
            return redirect()->route('calendar.index')
                ->with('error', 'Connecting Google Calendar was cancelled: '.$request->query('error'));
        }

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $state)) {
            return redirect()->route('calendar.index')
                ->with('error', 'Connecting Google Calendar failed: invalid state.');
        }

        if ($code === '') {
            return redirect()->route('calendar.index')
                ->with('error', 'Connecting Google Calendar failed: no authorization code received.');
        }

        $tokens->exchangeCode($code);

        return redirect()->route('calendar.index')
            ->with('success', 'Google Calendar connected.');
    }

    private function validatedGoal(Request $request, bool $create = true): array
    {
        return $request->validate([
            'title' => [$create ? 'required' : 'sometimes', 'string', 'max:160'],
            'category' => [$create ? 'required' : 'sometimes', 'in:sport,family,date,custom'],
            'frequency_type' => [$create ? 'required' : 'sometimes', 'in:times_per_week,weekly'],
            'target_min' => 'sometimes|integer|min:1|max:7',
            'target_max' => 'sometimes|integer|min:1|max:7',
            'preferred_windows' => 'nullable|array',
            'duration_minutes' => 'sometimes|integer|min:15|max:480',
            'active' => 'sometimes|boolean',
            'plannable' => 'sometimes|boolean',
        ]);
    }
}
