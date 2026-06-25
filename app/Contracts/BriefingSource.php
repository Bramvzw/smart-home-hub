<?php

namespace App\Contracts;

use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;

interface BriefingSource
{
    public function key(): string;

    public function label(): string;

    public function priority(): int;

    public function contribute(CarbonImmutable $date): ?BriefingSection;
}
