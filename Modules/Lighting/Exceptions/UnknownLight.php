<?php

namespace Modules\Lighting\Exceptions;

use RuntimeException;

class UnknownLight extends RuntimeException
{
    public static function forTarget(string $provider, string $id): self
    {
        return new self("Unknown light [{$provider}:{$id}].");
    }
}
