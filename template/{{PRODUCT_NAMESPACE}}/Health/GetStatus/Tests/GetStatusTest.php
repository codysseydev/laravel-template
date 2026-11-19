<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Health\GetStatus\Tests;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GetStatusTest extends TestCase
{
    #[Test]
    public function it_reports_the_app_and_a_reachable_database(): void
    {
        $this->getJson(route('health.status'))
            ->assertOk()
            ->assertJsonPath('data.environment', 'testing')
            ->assertJsonPath('data.database_reachable', true);
    }
}
