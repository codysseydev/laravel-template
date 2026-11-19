<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Lazy loading is blocked only outside production, so a relation read lazily
     * passes in production and fails on a developer machine. Every test runs
     * strict instead.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);

        parent::tearDown();
    }
}
