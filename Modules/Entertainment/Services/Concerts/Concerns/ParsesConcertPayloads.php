<?php

namespace Modules\Entertainment\Services\Concerts\Concerns;

use Carbon\CarbonImmutable;
use Modules\Entertainment\Data\ConcertData;

trait ParsesConcertPayloads
{
    private function parseConcerts(string $source, array $payload): array
    {
        $concerts = [];

        foreach ($this->items($payload) as $item) {
            $artist = $this->str(data_get($item, 'artist') ?? data_get($item, 'name') ?? data_get($item, 'title') ?? data_get($item, '_embedded.attractions.0.name'));
            $date = $this->date(data_get($item, 'date') ?? data_get($item, 'datetime') ?? data_get($item, 'dates.start.dateTime') ?? data_get($item, 'startDate'));

            if (! $artist || ! $date) {
                continue;
            }

            $concerts[] = new ConcertData(
                source: $source,
                externalId: $this->str(data_get($item, 'external_id') ?? data_get($item, 'externalId') ?? data_get($item, 'id')),
                artist: $artist,
                title: $this->str(data_get($item, 'title') ?? data_get($item, 'name')),
                venue: $this->str(data_get($item, 'venue') ?? data_get($item, '_embedded.venues.0.name')),
                city: $this->str(data_get($item, 'city') ?? data_get($item, '_embedded.venues.0.city.name')),
                date: $date,
                url: $this->str(data_get($item, 'url') ?? data_get($item, 'link')),
            );
        }

        return $concerts;
    }

    private function items(array $payload): array
    {
        return data_get($payload, '_embedded.events')
            ?? data_get($payload, 'events')
            ?? data_get($payload, 'items')
            ?? (array_is_list($payload) ? $payload : []);
    }

    private function str(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
