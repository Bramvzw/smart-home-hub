<?php

namespace Modules\Briefing\Tests\Unit;

use App\Support\Briefing\BriefingSection;
use Modules\Briefing\Contracts\BriefingTextGenerator;
use Modules\Briefing\Services\BriefingComposer;
use Modules\Briefing\Services\TemplatedBriefingComposer;
use Tests\TestCase;

class BriefingComposerConfigTest extends TestCase
{
    public function test_system_prompt_reflects_default_dutch_informal_medium_config(): void
    {
        config([
            'briefing.language' => 'nl',
            'briefing.tone' => 'informal',
            'briefing.length' => 'medium',
        ]);

        $generator = new RecordingBriefingTextGenerator;
        $composer = new BriefingComposer($generator, app(TemplatedBriefingComposer::class));

        $composer->compose([new BriefingSection('weather', 'Weer', 10, 'Droog')]);

        $this->assertStringContainsString('in het Nederlands', $generator->systemPrompt);
        $this->assertStringContainsString('Gebruik je/jij, informeel', $generator->systemPrompt);
        $this->assertStringContainsString('korte alinea', $generator->systemPrompt);
        $this->assertStringContainsString('medium-lange ochtendbriefing', $generator->prompt);
    }

    public function test_system_prompt_reflects_english_formal_long_config(): void
    {
        config([
            'briefing.language' => 'en',
            'briefing.tone' => 'formal',
            'briefing.length' => 'long',
        ]);

        $generator = new RecordingBriefingTextGenerator;
        $composer = new BriefingComposer($generator, app(TemplatedBriefingComposer::class));

        $composer->compose([new BriefingSection('weather', 'Weer', 10, 'Droog')]);

        $this->assertStringContainsString('in het Engels', $generator->systemPrompt);
        $this->assertStringContainsString('Gebruik u, formeel', $generator->systemPrompt);
        $this->assertStringContainsString('uitgebreide alinea', $generator->systemPrompt);
        $this->assertStringContainsString('uitgebreide ochtendbriefing', $generator->prompt);
    }
}

class RecordingBriefingTextGenerator implements BriefingTextGenerator
{
    public string $systemPrompt = '';

    public string $prompt = '';

    public function generate(array $sections, string $systemPrompt, string $prompt): string
    {
        $this->systemPrompt = $systemPrompt;
        $this->prompt = $prompt;

        return 'AI body';
    }
}
