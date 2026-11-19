<?php

declare(strict_types=1);

use Framework\Providers\AppServiceProvider;

$product = require __DIR__.'/../modules.php';

return [
    AppServiceProvider::class,
    ...array_map(
        static fn (string $module): string => $product['namespace'].'\\'.$module.'\\'.$module.'ServiceProvider',
        $product['modules'],
    ),
];
