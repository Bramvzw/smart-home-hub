<?php

namespace Modules\Entertainment\Contracts;

use Illuminate\Support\Collection;
use Modules\Entertainment\Data\FilmPick;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\TasteProfile;

interface EntertainmentCurator
{
    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return list<FilmPick>
     */
    public function curateFilms(array $candidates, TasteProfile $profile, Collection $feedback): array;

    /**
     * @param  list<string>  $followedArtists
     */
    public function concertRelevance(Concert $concert, array $followedArtists, TasteProfile $profile): string;
}
