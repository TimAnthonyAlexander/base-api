<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        // Exclude vendor, build artifacts, caches, and dynamic/generated files
        __DIR__ . '/vendor',
        __DIR__ . '/build',
        __DIR__ . '/storage/cache',
        __DIR__ . '/storage/logs',
        __DIR__ . '/storage/ratelimits',

        // Skip magic/dynamic areas where property promotion might break things
        __DIR__ . '/src/Container', // Dependency injection container might rely on dynamic properties
        __DIR__ . '/src/Models/BaseModel.php', // ORM-like models might use __set/__get

        // Skip specific transformations that might be risky for library code
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/src/Http/Request.php', // Request handling might need explicit properties
            __DIR__ . '/src/Http/Response.php', // Response handling might need explicit properties
            __DIR__ . '/src/Models', // Model classes might be extended by users
        ],
        StringClassNameToClassConstantRector::class,
        RemoveEmptyClassMethodRector::class => [
            __DIR__ . '/tests/ContainerTest.php',
        ],
        RemoveUnusedPromotedPropertyRector::class => [
            __DIR__ . '/tests/ContainerTest.php',
        ],
        RemoveParentCallWithoutParentRector::class => [
            __DIR__ . '/src/Database/Relations', // Keep model instance static method calls
        ],
    ])
    ->withSets([
        // Core language modernization to PHP 8.4
        LevelSetList::UP_TO_PHP_84,

        // Quality improvements that are safe for libraries
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,

        SetList::CODING_STYLE,
    ])
    ->withImportNames(
        importShortClasses: true,
        removeUnusedImports: true
    )
    ->withParallel()
    ->withCache(__DIR__ . '/var/cache/rector')
    ->withPhpSets(php84: true);
