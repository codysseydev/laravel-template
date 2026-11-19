<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Health;

use {{PRODUCT_NAMESPACE}}\Health\GetStatus\GetStatusQuery;
use {{PRODUCT_NAMESPACE}}\Health\GetStatus\GetStatusQueryHandler;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\ServiceProvider;

final class HealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
        $this->loadRoutesFrom(__DIR__.'/health-routes.php');

        Bus::map([
            GetStatusQuery::class => GetStatusQueryHandler::class,
        ]);
    }
}
