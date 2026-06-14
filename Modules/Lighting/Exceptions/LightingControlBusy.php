<?php

namespace Modules\Lighting\Exceptions;

use RuntimeException;
use Throwable;

class LightingControlBusy extends RuntimeException
{
    public static function queueTimeout(?Throwable $previous = null): self
    {
        return new self('Er loopt al een lampactie. Probeer het zo opnieuw.', 0, $previous);
    }
}
