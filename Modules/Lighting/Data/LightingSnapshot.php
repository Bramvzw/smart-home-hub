<?php

namespace Modules\Lighting\Data;

final readonly class LightingSnapshot
{
    /**
     * @param  list<Light>  $lights
     * @param  list<string>  $unreachableProviders  Labels of providers that could not be reached.
     */
    public function __construct(
        public array $lights,
        public array $unreachableProviders = [],
    ) {}
}
