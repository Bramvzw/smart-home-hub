<?php

namespace Modules\Lighting\Exceptions;

use RuntimeException;
use Throwable;

class LightingControlBusy extends RuntimeException
{
    public static function queueTimeout(?Throwable $previous = null): self
    {
        return new self('A lighting action is already running. Try again in a moment.', 0, $previous);
    }
}
