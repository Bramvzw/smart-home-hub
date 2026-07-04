<?php

namespace Modules\Briefing\Tests\Unit;

use App\Support\Briefing\BriefingSection;
use Modules\Briefing\Services\TemplatedBriefingComposer;
use Tests\TestCase;

class TemplatedBriefingComposerTest extends TestCase
{
    public function test_it_produces_deterministic_text_from_sections(): void
    {
        $briefing = app(TemplatedBriefingComposer::class)->compose([
            new BriefingSection('weather', 'Weather', 10, '24°C and dry'),
            new BriefingSection('tasks', 'Tasks', 30, 'Top 2 open tasks'),
        ]);

        $this->assertTrue($briefing->isFallback);
        $this->assertNull($briefing->model);
        $this->assertSame("Good morning! This is your briefing for today.\n\nWeather: 24°C and dry\n\nTasks: Top 2 open tasks", $briefing->body);
    }
}
