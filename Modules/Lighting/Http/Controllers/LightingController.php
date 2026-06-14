<?php

namespace Modules\Lighting\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Lighting\Actions\ApplyLightingPreset;
use Modules\Lighting\Actions\ControlLight;
use Modules\Lighting\Exceptions\LightingControlBusy;
use Modules\Lighting\Exceptions\UnknownLightingPreset;
use Modules\Lighting\Http\Resources\LightResource;
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
            return response()->json(['message' => 'Onbekende preset.'], 404);
        } catch (LightingControlBusy $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['data' => $result->toArray()]);
    }

    public function update(Request $request, string $provider, string $id, ControlLight $control): JsonResponse
    {
        $changes = $request->validate([
            'power' => ['sometimes', 'boolean'],
            'brightness' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'color' => ['sometimes', 'string', 'regex:/^#?[0-9a-fA-F]{6}$/'],
        ]);

        if (isset($changes['color']) && ! str_starts_with($changes['color'], '#')) {
            $changes['color'] = '#'.$changes['color'];
        }

        try {
            $light = $control($provider, $id, $changes);
        } catch (LightingControlBusy $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (RuntimeException) {
            // Per-device isolation: surface a clean failure, never provider internals.
            return response()->json(['message' => 'De lamp kon niet worden bijgewerkt.'], 502);
        }

        return LightResource::make($light)->response();
    }
}
