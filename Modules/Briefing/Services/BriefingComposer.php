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
        $language = $this->languageInstruction((string) config('briefing.language', 'nl'));
        $tone = $this->toneInstruction((string) config('briefing.tone', 'informal'));
        $length = $this->lengthInstruction((string) config('briefing.length', 'medium'));

        return implode(' ', [
            "Je schrijft een dagelijkse ochtendbriefing {$language}.",
            $tone,
            $length,
            'Gebruik alleen feiten uit de input en verzin geen ontbrekende data.',
            'Laat secties zonder data weg.',
        ]);
    }

    private function languageInstruction(string $language): string
    {
        return match ($language) {
            'en' => 'in het Engels',
            default => 'in het Nederlands',
        };
    }

    private function toneInstruction(string $tone): string
    {
        return match ($tone) {
            'formal' => 'Gebruik u, formeel en zakelijk, maar blijf compact.',
            default => 'Gebruik je/jij, informeel en vriendelijk, maar blijf compact.',
        };
    }

    private function lengthInstruction(string $length): string
    {
        return match ($length) {
            'short' => 'Schrijf maximaal een korte zin per aangeleverde sectie.',
            'long' => 'Schrijf een uitgebreide alinea per aangeleverde sectie.',
            default => 'Schrijf een korte alinea per aangeleverde sectie.',
        };
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

        $length = match ((string) config('briefing.length', 'medium')) {
            'short' => 'korte',
            'long' => 'uitgebreide',
            default => 'medium-lange',
        };

        return "Maak een {$length} ochtendbriefing uit deze gestructureerde secties:\n\n{$payload}";
    }
}
