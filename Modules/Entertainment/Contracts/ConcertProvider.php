<?php

namespace Modules\Entertainment\Contracts;

use Modules\Entertainment\Data\ConcertData;

interface ConcertProvider
{
    public function source(): string;

    /**
     * @return list<ConcertData>
     */
    public function fetch(): array;
}
