<?php

namespace Modules\Briefing\Services;

use App\Support\Briefing\BriefingSection;
use Modules\Briefing\Data\ComposedBriefing;

class TemplatedBriefingComposer
{
    /**
     * @param  list<BriefingSection>  $sections
     */
    public function compose(array $sections): ComposedBriefing
    {
        if ($sections === []) {
            return new ComposedBriefing(
                body: 'Goedemorgen! Ik heb voor vandaag nog geen concrete updates gevonden. Rustige start dus.',
                model: null,
                isFallback: true,
            );
        }

        $paragraphs = ['Goedemorgen! Dit is je briefing voor vandaag.'];

        foreach ($sections as $section) {
            $paragraphs[] = sprintf('%s: %s', $section->label, $section->summary);
        }

        return new ComposedBriefing(
            body: implode("\n\n", $paragraphs),
            model: null,
            isFallback: true,
        );
    }
}
