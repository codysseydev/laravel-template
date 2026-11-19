<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClassShapeRulesTest extends TestCase
{
    #[Test]
    public function it_reports_a_non_final_controller(): void
    {
        [$controllersMustBeFinal] = ClassShapeRules::rules();

        $violations = new Violations;
        $controllersMustBeFinal->check(
            ClassDescription::getBuilder('Fake\FakeController', '/fake/FakeController.php')->setFinal(false)->build(),
            $violations,
        );

        self::assertCount(1, $violations);
    }

    #[Test]
    public function it_accepts_a_final_controller(): void
    {
        [$controllersMustBeFinal] = ClassShapeRules::rules();

        $violations = new Violations;
        $controllersMustBeFinal->check(
            ClassDescription::getBuilder('Fake\FakeController', '/fake/FakeController.php')->setFinal(true)->build(),
            $violations,
        );

        self::assertCount(0, $violations);
    }
}
