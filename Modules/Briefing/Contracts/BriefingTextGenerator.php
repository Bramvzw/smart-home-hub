<?php

namespace Modules\Briefing\Contracts;

interface BriefingTextGenerator
{
    /**
     * @param  list<array<string, mixed>>  $sections
     */
    public function generate(array $sections, string $systemPrompt, string $prompt): string;
}
