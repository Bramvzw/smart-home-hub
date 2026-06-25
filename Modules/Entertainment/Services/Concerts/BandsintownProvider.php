<?php

namespace Modules\Entertainment\Services\Concerts;

use Illuminate\Support\Facades\Http;
use Modules\Entertainment\Contracts\ConcertProvider;
use Modules\Entertainment\Exceptions\EntertainmentSourceUnavailable;
use Modules\Entertainment\Services\Concerts\Concerns\ParsesConcertPayloads;

class BandsintownProvider implements ConcertProvider
{
    use ParsesConcertPayloads;

    public function source(): string
    {
        return 'bandsintown';
    }

    public function fetch(): array
    {
        $key = (string) config('entertainment.concerts.bandsintown_key', '');

        if ($key === '') {
            return [];
        }

        $response = Http::timeout(15)->acceptJson()->get('https://rest.bandsintown.com/events/search', [
            'app_id' => $key,
            'location' => 'Netherlands',
        ]);

        if (! $response->successful()) {
            throw new EntertainmentSourceUnavailable('Bandsintown returned HTTP '.$response->status().'.');
        }

        return $this->parseConcerts($this->source(), $response->json() ?? []);
    }
}
