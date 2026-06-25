<?php

namespace Modules\Briefing\Services;

use App\Support\Briefing\BriefingSection;
use Illuminate\Support\Facades\Log;
use Modules\Briefing\Contracts\BriefingTextGenerator;
use Modules\Briefing\Data\ComposedBriefing;
use Throwable;

class BriefingComposer
{
    public function __construct(
        private readonly BriefingTextGenerator $generator,
        private readonly TemplatedBriefingComposer $fallback,
    ) {}

    /**
     * @param  list<BriefingSection>  $sections
     */
    public function compose(array $sections): ComposedBriefing
    {
        try {
            $body = $this->generator->generate(
                sections: array_map(fn (BriefingSection $section): array => $section->toArray(), $sections),
                systemPrompt: $this->systemPrompt(),
                prompt: $this->prompt($sections),
            );

            return new ComposedBriefing(
                body: $body,
                model: (string) config('briefing.ai.model', 'claude-sonnet-4-6'),
                isFallback: false,
            );
        } catch (Throwable $exception) {
            Log::warning('AI briefing composition failed; using fallback.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->fallback->compose($sections);
        }
    }

    private function systemPrompt(): string
    {
        return implode(' ', [
            'Je schrijft een dagelijkse ochtendbriefing in het Nederlands.',
            'Gebruik je/jij, informeel en vriendelijk, maar blijf compact.',
            'Schrijf een korte alinea per aangeleverde sectie.',
            'Gebruik alleen feiten uit de input en verzin geen ontbrekende data.',
            'Laat secties zonder data weg.',
        ]);
    }

    /**
     * @param  list<BriefingSection>  $sections
     */
    private function prompt(array $sections): string
    {
        $payload = json_encode(
            array_map(fn (BriefingSection $section): array => $section->toArray(), $sections),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        return "Maak een medium-lange ochtendbriefing uit deze gestructureerde secties:\n\n{$payload}";
    }
}
