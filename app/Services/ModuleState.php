<?php

namespace App\Services;

use App\Services\Settings\SettingsStore;

/**
 * Visibility and ordering state for modules, stored through the SettingsStore.
 *
 * bootstrap/providers.php stays the single source of truth for which modules
 * are loaded; this state only controls whether a module is shown and where it
 * appears in the navigation, on the dashboard and in the scheduler. Disabled
 * modules keep their routes, bindings and briefing sources registered.
 */
class ModuleState
{
    public function __construct(private readonly SettingsStore $settings) {}

    public function isEnabled(string $slug): bool
    {
        return (bool) $this->settings->get("modules.{$slug}.enabled", true);
    }

    /**
     * Configured position of a module, falling back to its registration order.
     */
    public function order(string $slug, int $registrationOrder): int
    {
        return (int) $this->settings->get("modules.{$slug}.order", $registrationOrder);
    }

    public function update(string $slug, bool $enabled, int $order): void
    {
        $this->settings->set("modules.{$slug}.enabled", $enabled);
        $this->settings->set("modules.{$slug}.order", $order);
    }
}
