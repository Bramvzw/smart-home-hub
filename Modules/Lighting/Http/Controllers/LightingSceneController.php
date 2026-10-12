<?php

namespace Modules\Lighting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Lighting\Actions\Scenes\ApplyLightingScene;
use Modules\Lighting\Actions\Scenes\CreateLightingScene;
use Modules\Lighting\Actions\Scenes\DeleteLightingScene;
use Modules\Lighting\Actions\Scenes\UpdateLightingScene;
use Modules\Lighting\Data\LightPreset;
use Modules\Lighting\Exceptions\LightingControlBusy;
use Modules\Lighting\Exceptions\UnknownLight;
use Modules\Lighting\Http\Requests\StoreLightingSceneRequest;
use Modules\Lighting\Http\Requests\UpdateLightingSceneRequest;
use Modules\Lighting\Http\Resources\LightingSceneResource;
use Modules\Lighting\Models\LightingScene;
use Modules\Lighting\Services\LightingService;

class LightingSceneController
{
    /** Built-in (config) presets first, then the user's own scenes. */
    public function index(LightingService $service): JsonResponse
    {
        return response()->json(['data' => [
            'presets' => array_map(static fn (LightPreset $preset): array => $preset->toArray(), $service->presets()),
            'scenes' => LightingSceneResource::collection(LightingScene::query()->ordered()->with('lights')->get())->resolve(),
        ]]);
    }

    public function store(StoreLightingSceneRequest $request, CreateLightingScene $create): JsonResponse
    {
        try {
            $scene = $create((string) $request->validated('name'), (string) $request->validated('accent'), $request->lights());
        } catch (UnknownLight) {
            return $this->unknownLight();
        }

        return LightingSceneResource::make($scene)->response()->setStatusCode(201);
    }

    public function update(UpdateLightingSceneRequest $request, LightingScene $scene, UpdateLightingScene $update): JsonResponse
    {
        try {
            $scene = $update($scene, $request->name(), $request->accent(), $request->lights());
        } catch (UnknownLight) {
            return $this->unknownLight();
        }

        return LightingSceneResource::make($scene)->response();
    }

    public function destroy(LightingScene $scene, DeleteLightingScene $delete): Response
    {
        $delete($scene);

        return response()->noContent();
    }

    public function apply(LightingScene $scene, ApplyLightingScene $apply): JsonResponse
    {
        try {
            $result = $apply($scene);
        } catch (LightingControlBusy $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        // Partial failures (e.g. an unreachable lamp) stay 200 with `failed` filled in.
        return response()->json(['data' => $result->toArray()], $result->failedCompletely() ? 502 : 200);
    }

    private function unknownLight(): JsonResponse
    {
        return response()->json(['message' => 'Unknown light.', 'errors' => ['lights' => ['Unknown light.']]], 422);
    }
}
