<?php

namespace Modules\Entertainment\Services\Concerts;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Modules\Entertainment\Contracts\ConcertProvider;
use Modules\Entertainment\Exceptions\EntertainmentSourceUnavailable;
use Modules\Entertainment\Services\Concerts\Concerns\ParsesConcertPayloads;

class TicketmasterProvider implements ConcertProvider
{
    use ParsesConcertPayloads;

    public function source(): string
    {
        return 'ticketmaster';
    }

    public function fetch(): array
    {
        $key = (string) config('entertainment.concerts.ticketmaster_key', '');

        if ($key === '') {
            return [];
        }

        $response = Http::timeout(15)->acceptJson()->get('https://app.ticketmaster.com/discovery/v2/events.json', [
            'apikey' => $key,
            'countryCode' => 'NL',
            'classificationName' => 'music',
            'startDateTime' => CarbonImmutable::now()->toIso8601ZuluString(),
            'sort' => 'date,asc',
            'size' => 100,
        ]);

        if (! $response->successful()) {
            throw new EntertainmentSourceUnavailable('Ticketmaster returned HTTP '.$response->status().'.');
        }

        return $this->parseConcerts($this->source(), $response->json() ?? []);
    }
}
