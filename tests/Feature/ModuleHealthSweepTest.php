<?php

namespace Tests\Feature;

use App\Contracts\ModuleContract;
use App\Contracts\ReportsHealth;
use App\Services\Health\ModuleHealthSweep;
use App\Services\ModuleRegistry;
use App\Services\Ntfy\HubNotifier;
use App\Support\Health\ModuleHealth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ModuleHealthSweepTest extends TestCase
{
    private ModuleRegistry $registry;

    private FakeSweepNotifier $notifier;

    private FakeHealthModule $module;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('health:sweep:statuses');

        $this->registry = new ModuleRegistry;
        $this->notifier = new FakeSweepNotifier;
        $this->module = new FakeHealthModule('lighting', ModuleHealth::ok());
        $this->registry->register($this->module);
    }

    public function test_the_first_sweep_records_a_baseline_without_notifying(): void
    {
        $this->module->health = ModuleHealth::degraded(['api broke']);

        $statuses = $this->sweep()();

        $this->assertSame(['lighting' => 'degraded'], $statuses);
        $this->assertSame([], $this->notifier->sent);
    }

    public function test_a_regression_after_an_ok_baseline_notifies_with_the_issues(): void
    {
        $this->sweep()();

        $this->module->health = ModuleHealth::degraded(['api broke']);
        $this->sweep()();

        $this->assertCount(1, $this->notifier->sent);
        $this->assertSame('Module health', $this->notifier->sent[0]['title']);
        $this->assertStringContainsString('lighting: api broke', $this->notifier->sent[0]['message']);
    }

    public function test_a_persistent_regression_does_not_notify_again(): void
    {
        $this->sweep()();

        $this->module->health = ModuleHealth::degraded(['api broke']);
        $this->sweep()();
        $this->sweep()();

        $this->assertCount(1, $this->notifier->sent);
    }

    public function test_a_recovery_notifies_once(): void
    {
        $this->sweep()();

        $this->module->health = ModuleHealth::degraded(['api broke']);
        $this->sweep()();

        $this->module->health = ModuleHealth::ok();
        $this->sweep()();

        $this->assertCount(2, $this->notifier->sent);
        $this->assertSame('Module health recovered', $this->notifier->sent[1]['title']);
        $this->assertStringContainsString('lighting', $this->notifier->sent[1]['message']);
    }

    private function sweep(): ModuleHealthSweep
    {
        return new ModuleHealthSweep($this->registry, $this->notifier);
    }
}

class FakeSweepNotifier extends HubNotifier
{
    public array $sent = [];

    public function __construct()
    {
        parent::__construct('https://ntfy.sh', 'topic', '');
    }

    public function sendWithOptions(string $title, string $message, ?string $tags, string $priority): void
    {
        $this->sent[] = compact('title', 'message');
    }
}

class FakeHealthModule implements ModuleContract, ReportsHealth
{
    public function __construct(
        private readonly string $slug,
        public ModuleHealth $health,
    ) {}

    public function getModuleName(): string
    {
        return ucfirst($this->slug);
    }

    public function getModuleSlug(): string
    {
        return $this->slug;
    }

    public function getNavigation(): array
    {
        return [];
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }

    public function health(): ModuleHealth
    {
        return $this->health;
    }
}
