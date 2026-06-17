<?php

namespace App\Dashboard;

final class SidebarState
{
    public const COOKIE = 'sidebar_state';

    public const DEFAULT = 'expanded';

    /** @var list<string> */
    public const STATES = ['expanded', 'rail', 'hidden'];

    public static function resolve(?string $cookie): string
    {
        return in_array($cookie, self::STATES, true) ? $cookie : self::DEFAULT;
    }
}
