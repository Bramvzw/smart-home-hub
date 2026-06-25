<?php

namespace Modules\Entertainment\Actions;

use Modules\Entertainment\Models\TasteProfile;

class UpdateTasteProfile
{
    public function __invoke(array $data): TasteProfile
    {
        $profile = TasteProfile::query()->firstOrCreate([], ['favorite_titles' => [], 'genres' => []]);
        $profile->update(collect($data)->only(['favorite_titles', 'genres', 'notes'])->all());

        return $profile->fresh();
    }
}
