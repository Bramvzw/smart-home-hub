<?php

namespace Modules\Entertainment\Briefing;

use App\Contracts\BriefingSource;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\MusicRelease;

class EntertainmentBriefingSource implements BriefingSource
{
    /**
     * @var list<string>
     */
    private const RELEVANT = ['followed', 'hedon', 'might_like'];

    public function key(): string
    {
        return 'entertainment';
    }

    public function label(): string
    {
        return 'Entertainment';
    }

    public function priority(): int
    {
        return 70;
    }

    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        $concerts = Concert::query()
            ->whereIn('relevance', self::RELEVANT)
            ->whereBetween('date', [$date->startOfDay(), $date->addDays(14)->endOfDay()])
            ->orderBy('date')
            ->get();

        $releases = MusicRelease::query()
            ->where('notified', false)
            ->orderBy('release_date')
            ->get();

        if ($concerts->isEmpty() && $releases->isEmpty()) {
            return null;
        }

        $summaries = [];

        if ($concerts->isNotEmpty()) {
            $summaries[] = $concerts->count().' '.($concerts->count() === 1 ? 'concert' : 'concerten').' deze/volgende week: '.$concerts
                ->map(fn (Concert $concert): string => trim("{$concert->artist} @ {$concert->venue}").' ('.$concert->date->format('d-m').')')
                ->implode(', ');
        }

        if ($releases->isNotEmpty()) {
            $summaries[] = $releases->count().' nieuwe release'.($releases->count() === 1 ? '' : 's').': '.$releases
                ->map(fn (MusicRelease $release): string => "{$release->artist} - {$release->title} ({$release->type})")
                ->implode(', ');
        }

        return new BriefingSection(
            key: $this->key(),
            label: $this->label(),
            priority: $this->priority(),
            summary: implode(' | ', $summaries),
            data: [
                'concerts' => $concerts->map(fn (Concert $concert): array => [
                    'artist' => $concert->artist,
                    'title' => $concert->title,
                    'venue' => $concert->venue,
                    'city' => $concert->city,
                    'date' => $concert->date->toIso8601String(),
                    'relevance' => $concert->relevance,
                    'url' => $concert->url,
                ])->values()->all(),
                'releases' => $releases->map(fn (MusicRelease $release): array => [
                    'artist' => $release->artist,
                    'title' => $release->title,
                    'type' => $release->type,
                    'release_date' => $release->release_date->toDateString(),
                    'url' => $release->url,
                ])->values()->all(),
            ],
        );
    }
}
