<?php

namespace Modules\Lighting\Exceptions;

use RuntimeException;

class UnknownLightingPreset extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self("Unknown lighting preset [{$key}].");
    }
}
