<?php

namespace App\Enums;

enum ModuleHealthStatus: string
{
    case Ok = 'ok';
    case NeedsSetup = 'needs_setup';
    case Degraded = 'degraded';
}
