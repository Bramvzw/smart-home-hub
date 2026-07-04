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
                body: 'Good morning! No concrete updates for today yet — a quiet start.',
                model: null,
                isFallback: true,
            );
        }

        $paragraphs = ['Good morning! This is your briefing for today.'];

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
