<?php

declare(strict_types=1);

/**
 * PHPStan reads the module list from modules.php, so a new module is analysed
 * with no edit to phpstan.neon.
 */
$product = require __DIR__.'/modules.php';

$paths = ['Framework/', 'tools/'];
$migrations = ['database/migrations'];

foreach ($product['modules'] as $module) {
    $paths[] = $product['namespace'].'/'.$module.'/';

    $migrationFolder = $product['namespace'].'/'.$module.'/Migrations';

    if (is_dir(__DIR__.'/'.$migrationFolder)) {
        $migrations[] = $migrationFolder;
    }
}

return [
    'parameters' => [
        'paths' => $paths,
        'databaseMigrationsPath' => $migrations,
    ],
];
