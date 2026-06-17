<?php

namespace Modules\PhonePing\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Modules\PhonePing\Actions\PingPhone;
use Modules\PhonePing\Services\NtfyClient;

class PhonePingController
{
    public function index(NtfyClient $ntfy): View
    {
        return view('phoneping::index', [
            'configured' => $ntfy->isConfigured(),
        ]);
    }

    public function ping(PingPhone $pingPhone): JsonResponse
    {
        $result = $pingPhone();

        return response()->json(
            ['message' => $result->message],
            $result->success ? 200 : 502,
        );
    }
}
