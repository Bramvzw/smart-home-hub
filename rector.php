<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelLevelSetList;

// Modernization gate: the whole codebase stays on current PHP/Laravel idiom.
// UP_TO_PHP_82 matches the composer "php" constraint — raise them together.
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/Modules',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/Modules/*/resources/*',
        __DIR__.'/Modules/*/database/*',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        LaravelLevelSetList::UP_TO_LARAVEL_120,
        SetList::TYPE_DECLARATION,
        SetList::DEAD_CODE,
    ]);
