<?php

declare(strict_types=1);

namespace Framework\Providers\Tests;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AppServiceProviderTest extends TestCase
{
    #[Test]
    public function it_unguards_models_and_enables_strict_mode(): void
    {
        self::assertTrue(Model::isUnguarded());
        self::assertTrue(Model::preventsLazyLoading());
    }
}
