<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(codeQuality: true, deadCode: true, earlyReturn: true, typeDeclarations: true)
    ->withImportNames()
    ->withSkip([
        StrictArrayParamDimFetchRector::class,
    ]);
