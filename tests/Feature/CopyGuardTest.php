<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/**
 * Regression guards for finished migrations. See docs/vault/Decisions.md:
 * all user-facing copy is English, and PII never enters the tracked repo.
 */
class CopyGuardTest extends TestCase
{
    /**
     * Distinctly Dutch words that have no business in user-facing copy.
     * Deliberately conservative to avoid false positives on English words.
     */
    private const DUTCH_MARKERS = [
        'vandaag', 'goedemorgen', 'goedemiddag', 'goedenavond',
        'beschikbaar', 'aanbieding', 'boodschappen', 'weekmenu',
        'prijsdaling', 'ingesteld', 'verstuurd', 'opgehaald',
        'staan klaar', 'niet gelukt', 'instellingen',
        'goedkoper', 'opgeslagen', 'laagste ooit', 'ontbrekende',
    ];

    /**
     * Files that intentionally contain Dutch (see the decision log) or use
     * Dutch words as internal identifiers only.
     */
    private const DUTCH_ALLOWLIST = [
        // AI prompts stay Dutch: see the decision log (prompt language ≠ output
        // language for the briefing; = generated menu language for recipes).
        'Modules/Recipes/Services/PrismRecipeTextGenerator.php',
        'Modules/Briefing/Services/BriefingComposer.php',
        // Internal tab keys ('recepten'/'aanbiedingen'); visible labels are English.
        'Modules/Recipes/resources/views/index.blade.php',
        'Modules/Recipes/resources/assets/js/recepten.js',
    ];

    /** The only coordinates allowed in tracked files (neutral Amsterdam placeholder). */
    private const COORDINATE_PLACEHOLDERS = ['52.3676', '4.9041'];

    public function test_user_facing_sources_contain_no_dutch_copy(): void
    {
        $pattern = '/\b('.implode('|', array_map(
            static fn (string $w): string => preg_quote($w, '/'),
            self::DUTCH_MARKERS,
        )).')\b/i';

        $violations = [];

        foreach ($this->sourceFiles() as $relative => $contents) {
            if (in_array($relative, self::DUTCH_ALLOWLIST, true)) {
                continue;
            }

            foreach (explode("\n", $contents) as $lineNumber => $line) {
                if (preg_match($pattern, $line, $match) === 1) {
                    $violations[] = sprintf('%s:%d — "%s"', $relative, $lineNumber + 1, $match[1]);
                }
            }
        }

        $this->assertSame([], $violations, "Dutch copy found in user-facing sources (translate it, or add a decision-log entry plus allowlist line):\n".implode("\n", $violations));
    }

    public function test_no_real_coordinates_outside_the_neutral_placeholder(): void
    {
        $violations = [];

        foreach ($this->sourceFiles() as $relative => $contents) {
            foreach (explode("\n", $contents) as $lineNumber => $line) {
                // Only lines that talk about coordinates — bare decimals also
                // occur in SVG path data and are meaningless without context.
                if (preg_match('/lat|lng|lon|coord/i', $line) !== 1) {
                    continue;
                }

                preg_match_all('/\b\d{1,2}\.\d{4,}\b/', $line, $matches);

                foreach ($matches[0] as $candidate) {
                    if (! in_array($candidate, self::COORDINATE_PLACEHOLDERS, true)) {
                        $violations[] = sprintf('%s:%d — %s', $relative, $lineNumber + 1, $candidate);
                    }
                }
            }
        }

        $this->assertSame([], $violations, "Coordinate-like literals found (PII stays in untracked .env — use the Amsterdam placeholder in code):\n".implode("\n", $violations));
    }

    /**
     * @return iterable<string, string> relative path => file contents
     */
    private function sourceFiles(): iterable
    {
        $base = base_path();
        $roots = ['app', 'Modules', 'resources/views', 'config'];

        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base.'/'.$root, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! in_array($file->getExtension(), ['php', 'blade.php', 'js'], true) && ! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $relative = str_replace($base.'/', '', $file->getPathname());

                // Tests assert on copy; they are updated together with it.
                if (str_contains($relative, '/tests/')) {
                    continue;
                }

                yield $relative => (string) file_get_contents($file->getPathname());
            }
        }

        // .env.example ships defaults; it must be as clean as code.
        yield '.env.example' => (string) file_get_contents($base.'/.env.example');
    }
}
