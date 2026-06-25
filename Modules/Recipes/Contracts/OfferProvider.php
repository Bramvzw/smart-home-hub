<?php

namespace Modules\Recipes\Contracts;

use Modules\Recipes\Data\OfferData;

interface OfferProvider
{
    public function store(): string;

    /**
     * @return list<OfferData>
     */
    public function fetch(): array;
}
