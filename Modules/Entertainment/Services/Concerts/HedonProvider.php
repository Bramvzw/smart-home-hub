<?php

namespace Modules\Entertainment\Services\Concerts;

use Illuminate\Support\Facades\Http;
use Modules\Entertainment\Contracts\ConcertProvider;
use Modules\Entertainment\Exceptions\EntertainmentSourceUnavailable;
use Modules\Entertainment\Services\Concerts\Concerns\ParsesConcertPayloads;

class HedonProvider implements ConcertProvider
{
    use ParsesConcertPayloads;

    public function source(): string
    {
        return 'hedon';
    }

    public function fetch(): array
    {
        $url = (string) config('entertainment.concerts.hedon_agenda_url', '');

        if ($url === '') {
            return [];
        }

        $response = Http::timeout(15)->acceptJson()->get($url);

        if (! $response->successful()) {
            throw new EntertainmentSourceUnavailable('Hedon agenda returned HTTP '.$response->status().'.');
        }

        return $this->parseConcerts($this->source(), $response->json() ?? []);
    }
}
