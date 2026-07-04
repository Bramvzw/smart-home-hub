<?php

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * Machine-enforced AGENTS.md rules, run by PHPStan (composer analyse).
 *
 * Modules talk to each other via app/Contracts. The couplings excluded below
 * are the sanctioned cross-module features (see docs/vault/Decisions.md);
 * anything new must go through a contract instead of joining that list.
 */
final class ArchitectureTest
{
    private const MODULES = [
        'Briefing', 'Calendar', 'Deals', 'Entertainment', 'Lighting', 'News',
        'PhonePing', 'Printer', 'Recipes', 'Spotify', 'Tasks', 'Weather',
    ];

    /**
     * Sanctioned cross-module couplings: consumer => allowed providers.
     */
    private const SANCTIONED = [
        'Entertainment' => ['Spotify'],   // releases/library checks reuse the Spotify services
        'Lighting' => ['Weather'],        // weather-triggered presets read the forecast
    ];

    /** @return iterable<string, Rule> */
    public function test_modules_only_depend_on_their_own_module_and_app_contracts(): iterable
    {
        foreach (self::MODULES as $module) {
            $allowed = array_merge([$module], self::SANCTIONED[$module] ?? []);

            yield "module isolation: {$module}" => PHPat::rule()
                ->classes(Selector::inNamespace("Modules\\{$module}"))
                // Module tests may integration-test across modules.
                ->excluding(Selector::inNamespace("Modules\\{$module}\\Tests"))
                ->shouldNotDependOn()
                ->classes(Selector::inNamespace('Modules'))
                ->excluding(...array_map(
                    static fn (string $name) => Selector::inNamespace("Modules\\{$name}"),
                    $allowed,
                ))
                ->because('modules integrate via app/Contracts; new cross-module couplings need a contract (AGENTS.md)');
        }
    }

    public function test_shared_app_code_does_not_reach_into_modules(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App'))
            ->excluding(
                // Sanctioned: the dashboard page embeds the briefing read model.
                Selector::classname('App\Http\Controllers\DashboardController'),
            )
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('Modules'))
            ->because('app/ is shared glue; it must not know individual modules (AGENTS.md)');
    }
}
