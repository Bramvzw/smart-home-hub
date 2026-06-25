<?php

namespace Modules\Tasks\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tasks\Actions\Recurrences\CompleteHabit;
use Modules\Tasks\Actions\Recurrences\CompleteMaintenance;
use Modules\Tasks\Actions\Recurrences\CreateRecurrence;
use Modules\Tasks\Actions\Recurrences\DeleteRecurrence;
use Modules\Tasks\Actions\Recurrences\UndoHabitCompletion;
use Modules\Tasks\Actions\Recurrences\UpdateRecurrence;
use Modules\Tasks\Http\Requests\CompleteHabitRequest;
use Modules\Tasks\Http\Requests\StoreRecurrenceRequest;
use Modules\Tasks\Http\Requests\UpdateRecurrenceRequest;
use Modules\Tasks\Http\Resources\HabitResource;
use Modules\Tasks\Http\Resources\RecurrenceResource;
use Modules\Tasks\Models\TaskRecurrence;
use Modules\Tasks\View\ViewModels\HabitsPageViewModel;
use Modules\Tasks\View\ViewModels\TaskRecurrencesViewModel;

class TaskRecurrenceController
{
    public function __construct(
        private readonly TaskRecurrencesViewModel $viewModel,
        private readonly HabitsPageViewModel $pageViewModel,
    ) {
    }

    public function habits(Request $request): JsonResponse|View
    {
        $date = $this->date($request->query('date'));

        if ($request->expectsJson()) {
            return response()->json([
                'habits' => $this->viewModel->habits($date)
                    ->map(fn (array $habit): array => HabitResource::make($habit)->resolve($request))
                    ->values(),
                'date' => $date->toDateString(),
            ]);
        }

        return view('tasks::habits', $this->pageViewModel->pageData($date));
    }

    public function completeMaintenance(
        Request $request,
        TaskRecurrence $recurrence,
        CompleteMaintenance $completeMaintenance,
    ): JsonResponse {
        $date = $this->date($request->input('date'));
        $updated = $completeMaintenance($recurrence, $date);

        if (! $updated) {
            return response()->json(['message' => 'Not a maintenance task.'], 422);
        }

        return response()->json([
            'recurrence' => RecurrenceResource::make($updated)->resolve($request),
        ]);
    }

    public function complete(
        CompleteHabitRequest $request,
        TaskRecurrence $recurrence,
        CompleteHabit $completeHabit,
    ): JsonResponse {
        $date = $this->date($request->validated('date'));
        $completeHabit($recurrence, $date);

        return response()->json([
            'habit' => HabitResource::make($this->viewModel->habit($recurrence->fresh(), $date))->resolve($request),
        ]);
    }

    public function undoCompletion(
        CompleteHabitRequest $request,
        TaskRecurrence $recurrence,
        UndoHabitCompletion $undoHabitCompletion,
    ): JsonResponse {
        $date = $this->date($request->validated('date'));
        $removed = $undoHabitCompletion($recurrence, $date);

        return response()->json([
            'removed' => $removed,
            'habit' => HabitResource::make($this->viewModel->habit($recurrence->fresh(), $date))->resolve($request),
        ]);
    }

    public function maintenance(Request $request): JsonResponse
    {
        return response()->json([
            'recurrences' => $this->viewModel->maintenance()
                ->map(fn (TaskRecurrence $recurrence): array => RecurrenceResource::make($recurrence)->resolve($request))
                ->values(),
        ]);
    }

    public function store(StoreRecurrenceRequest $request, CreateRecurrence $createRecurrence): JsonResponse
    {
        $recurrence = $createRecurrence($request->validated());

        return response()->json([
            'recurrence' => RecurrenceResource::make($recurrence)->resolve($request),
        ], 201);
    }

    public function update(
        UpdateRecurrenceRequest $request,
        TaskRecurrence $recurrence,
        UpdateRecurrence $updateRecurrence,
    ): JsonResponse {
        $recurrence = $updateRecurrence($recurrence, $request->validated());

        return response()->json([
            'recurrence' => RecurrenceResource::make($recurrence)->resolve($request),
        ]);
    }

    public function destroy(TaskRecurrence $recurrence, DeleteRecurrence $deleteRecurrence): JsonResponse
    {
        $deleteRecurrence($recurrence);

        return response()->json(null, 204);
    }

    private function date(mixed $date = null): CarbonImmutable
    {
        if ($date) {
            return CarbonImmutable::parse((string) $date)->startOfDay();
        }

        return CarbonImmutable::now((string) config('app.timezone', 'UTC'))->startOfDay();
    }
}
