<?php

namespace App\Dashboard;

/**
 * Shared definition of the dashboard sidebar's collapse state.
 *
 * The state is persisted in an unencrypted cookie so the server can render it
 * without flashing the wrong state on navigation. The client mirror lives in
 * resources/js/sidebar.js.
 */
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
