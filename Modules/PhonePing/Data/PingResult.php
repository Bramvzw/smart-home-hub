<?php

namespace Modules\PhonePing\Data;

final readonly class PingResult
{
    public function __construct(
        public bool $success,
        public string $message,
    ) {}
}
