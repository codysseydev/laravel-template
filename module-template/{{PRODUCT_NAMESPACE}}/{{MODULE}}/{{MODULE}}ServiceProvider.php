<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\{{MODULE}};

use Illuminate\Support\ServiceProvider;

final class {{MODULE}}ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
        $this->loadRoutesFrom(__DIR__.'/{{MODULE_LOWER}}-routes.php');
    }
}
