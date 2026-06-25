<?php

namespace Modules\News\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Schema;
use Modules\News\Briefing\NewsBriefingSource;
use Modules\News\Models\NewsItem;

class NewsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'News';

    protected string $nameLower = 'news';

    public function register(): void
    {
        parent::register();

        $this->app->tag([NewsBriefingSource::class], 'briefing.source');
    }

    public function getModuleName(): string
    {
        return 'News';
    }

    public function getModuleSlug(): string
    {
        return 'news';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'News', 'route' => 'news.index', 'icon' => 'news'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        if (! Schema::hasTable('news_items')) {
            return null;
        }

        $unread = NewsItem::query()->unread()->count();

        return $unread === 1 ? '1 unread item' : "{$unread} unread items";
    }
}
