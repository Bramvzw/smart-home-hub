<?php

namespace Modules\Lighting\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Modules\Lighting\Actions\ApplyLightingPreset;
use Modules\Lighting\Actions\ControlLight;
use Modules\Lighting\Actions\ControlLights;
use Modules\Lighting\Exceptions\LightingControlBusy;
use Modules\Lighting\Exceptions\UnknownLight;
use Modules\Lighting\Exceptions\UnknownLightingPreset;
use Modules\Lighting\Http\Requests\ControlLightsRequest;
use Modules\Lighting\Http\Requests\UpdateLightRequest;
use Modules\Lighting\Http\Resources\LightResource;
use Modules\Lighting\Services\LightingService;
use Modules\Lighting\View\ViewModels\LightingViewModel;
use RuntimeException;

class LightingController
{
    public function index(LightingViewModel $viewModel): View
    {
        return view('lighting::index', $viewModel->page());
    }

    public function applyPreset(string $preset, ApplyLightingPreset $apply): JsonResponse
    {
        try {
            $result = $apply($preset);
        } catch (UnknownLightingPreset) {
            return response()->json(['message' => 'Unknown preset.'], 404);
        } catch (LightingControlBusy $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['data' => $result->toArray()]);
    }

    public function update(UpdateLightRequest $request, string $provider, string $id, ControlLight $control): JsonResponse
    {
        try {
            $light = $control($provider, $id, $request->changes());
        } catch (UnknownLight) {
            return response()->json(['message' => 'Unknown light.'], 422);
        } catch (LightingControlBusy $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (RuntimeException) {
            // Per-device isolation: surface a clean failure, never provider internals.
            return response()->json(['message' => 'The light could not be updated.'], 502);
        }

        return LightResource::make($light)->response();
    }

    public function updateMany(ControlLightsRequest $request, ControlLights $control): JsonResponse
    {
        try {
            $result = $control($request->lights(), $request->changes());
        } catch (UnknownLight) {
            return response()->json(['message' => 'Unknown light.', 'errors' => ['lights' => ['Unknown light.']]], 422);
        } catch (LightingControlBusy $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        // Partial failures stay 200 with `failed` filled in; only a full failure is a 502.
        return response()->json(['data' => $result->toArray()], $result->failedCompletely() ? 502 : 200);
    }

    public function state(LightingService $service): JsonResponse
    {
        // Served from the shared snapshot cache, so several open screens cost one provider call.
        $snapshot = $service->snapshot();

        return LightResource::collection($snapshot->lights)
            ->additional(['unreachable_providers' => $snapshot->unreachableProviders])
            ->response();
    }
}
