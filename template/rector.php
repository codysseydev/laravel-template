<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

$product = require __DIR__.'/modules.php';

// declare(strict_types=1) is enforced by Pint (pint.json), not Rector.
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/Framework',
        ...array_map(
            static fn (string $module): string => __DIR__.'/'.$product['namespace'].'/'.$module,
            $product['modules'],
        ),
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/public',
        __DIR__.'/routes',
        __DIR__.'/tests',
        __DIR__.'/tools',
    ])
    // bootstrap/cache holds generated files.
    ->withSkip([__DIR__.'/bootstrap/cache'])
    ->withTypeCoverageLevel(50)
    ->withDeadCodeLevel(49)
    ->withCodeQualityLevel(71)
    ->withCodingStyleLevel(25);
