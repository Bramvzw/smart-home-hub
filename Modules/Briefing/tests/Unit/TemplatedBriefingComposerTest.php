<?php

namespace Modules\Briefing\Tests\Unit;

use App\Support\Briefing\BriefingSection;
use Modules\Briefing\Services\TemplatedBriefingComposer;
use Tests\TestCase;

class TemplatedBriefingComposerTest extends TestCase
{
    public function test_it_produces_deterministic_dutch_text_from_sections(): void
    {
        $briefing = app(TemplatedBriefingComposer::class)->compose([
            new BriefingSection('weather', 'Weer', 10, '24°C en droog'),
            new BriefingSection('tasks', 'Taken', 30, 'Top 2 open taken'),
        ]);

        $this->assertTrue($briefing->isFallback);
        $this->assertNull($briefing->model);
        $this->assertSame("Goedemorgen! Dit is je briefing voor vandaag.\n\nWeer: 24°C en droog\n\nTaken: Top 2 open taken", $briefing->body);
    }
}
