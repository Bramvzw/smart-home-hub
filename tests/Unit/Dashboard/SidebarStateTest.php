<?php

namespace Tests\Unit\Dashboard;

use App\Dashboard\SidebarState;
use PHPUnit\Framework\TestCase;

class SidebarStateTest extends TestCase
{
    public function test_it_keeps_a_known_state(): void
    {
        $this->assertSame('rail', SidebarState::resolve('rail'));
        $this->assertSame('hidden', SidebarState::resolve('hidden'));
        $this->assertSame('expanded', SidebarState::resolve('expanded'));
    }

    public function test_it_falls_back_to_the_default_for_unknown_or_missing_values(): void
    {
        $this->assertSame(SidebarState::DEFAULT, SidebarState::resolve(null));
        $this->assertSame(SidebarState::DEFAULT, SidebarState::resolve(''));
        $this->assertSame(SidebarState::DEFAULT, SidebarState::resolve('bogus'));
    }
}
