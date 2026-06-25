<?php

namespace Modules\News\Actions;

use Modules\News\Data\NewsRefreshResult;
use Modules\News\Services\NewsService;

class RefreshFeeds
{
    public function __construct(
        private readonly NewsService $service,
    ) {}

    public function __invoke(): NewsRefreshResult
    {
        return $this->service->refresh();
    }
}
