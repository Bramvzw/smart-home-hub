<?php

namespace Modules\Entertainment\Actions;

use App\Services\Ntfy\HubNotifier;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\MusicRelease;

class NotifyEntertainment
{
    public function __construct(private readonly HubNotifier $notifier) {}

    public function __invoke(): array
    {
        $music = MusicRelease::query()->where('notified', false)->orderBy('release_date')->get();
        $concerts = Concert::query()->where('notified', false)->whereIn('relevance', ['followed', 'hedon', 'might_like'])->orderBy('date')->get();

        if ($music->isNotEmpty()) {
            $this->notifier->send('Nieuwe muziek', $music->map(fn (MusicRelease $release): string => "{$release->artist} - {$release->title} ({$release->type})")->implode("\n"));
            MusicRelease::query()->whereKey($music->pluck('id'))->update(['notified' => true]);
        }

        foreach ($concerts as $concert) {
            $this->notifier->send('Concerttip', "{$concert->artist} bij {$concert->venue} op {$concert->date->format('d-m-Y')} ({$concert->relevance}). {$concert->url}");
            $concert->update(['notified' => true]);
        }

        return ['music' => $music->count(), 'concerts' => $concerts->count()];
    }
}
