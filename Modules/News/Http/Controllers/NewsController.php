<?php

namespace Modules\News\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\News\Actions\CheckNewsKeywords;
use Modules\News\Actions\MarkAllRead;
use Modules\News\Actions\MarkItemsRead;
use Modules\News\Actions\RefreshFeeds;
use Modules\News\Models\NewsItem;
use Modules\News\View\ViewModels\NewsViewModel;

class NewsController
{
    public function __construct(
        private readonly NewsViewModel $viewModel,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return $this->items();
        }

        return view('news::index', $this->viewModel->state());
    }

    public function items(): JsonResponse
    {
        return response()->json($this->viewModel->state());
    }

    public function markRead(NewsItem $item, MarkItemsRead $markItemsRead): JsonResponse
    {
        $markItemsRead([$item->id]);

        return response()->json(['is_read' => true]);
    }

    public function readAll(Request $request, MarkAllRead $markAllRead): JsonResponse
    {
        $data = $request->validate([
            'topic' => ['nullable', 'string', Rule::in(array_keys((array) config('news.topics', [])))],
        ]);

        return response()->json([
            'marked' => $markAllRead($data['topic'] ?? null),
        ]);
    }

    public function refresh(RefreshFeeds $refreshFeeds, CheckNewsKeywords $checkNewsKeywords): JsonResponse
    {
        $result = $refreshFeeds();

        return response()->json(array_merge($result->toArray(), [
            'notified' => $checkNewsKeywords(),
        ]));
    }
}
