<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\{{MODULE}}\Tests;

use {{PRODUCT_NAMESPACE}}\{{MODULE}}\{{MODULE}}ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class {{MODULE}}ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_is_registered_through_the_module_list(): void
    {
        self::assertTrue($this->app->providerIsLoaded({{MODULE}}ServiceProvider::class));
    }
}
