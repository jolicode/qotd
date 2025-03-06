<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
    ])
    ->withPhpSets(php83: true)
    ->withImportNames(importShortClasses: false)
    ->withPreparedSets(
        privatization: true,
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
        doctrineCodeQuality: true,
    )
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        ExplicitBoolCompareRector::class,
        SimplifyIfElseToTernaryRector::class,
        'config/bundles.php',
    ])
;
