<?php

namespace Http;

use Illuminate\Http\JsonResponse;

class NotificationController
{
    public function index(): JsonResponse
    {
        // Logic to fetch and return notifications
        return response()->json([
            'message' => 'Notifications fetched successfully',
            'data' => [] // Replace with actual data
        ]);
    }
}
