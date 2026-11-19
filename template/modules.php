<?php

declare(strict_types=1);

/**
 * The one list of product modules.
 *
 * Provider registration (bootstrap/providers.php), PHPArkitect (phparkitect.php),
 * PHPStan (phpstan.modules.php), Rector (rector.php) and the module isolation rule
 * all read this file. PHPUnit scans the namespace folder, and the architecture
 * test ModuleListTest fails when a folder and this list disagree.
 *
 * Add a module here, create `<namespace>/<Module>/<Module>ServiceProvider.php`,
 * and every gate picks it up.
 *
 * @return array{namespace: string, modules: list<string>}
 */
return [
    'namespace' => '{{PRODUCT_NAMESPACE}}',
    'modules' => [
        'Health',
    ],
];
