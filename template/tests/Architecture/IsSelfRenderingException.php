<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;
use Illuminate\Http\JsonResponse;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Checked against the real, autoloaded class rather than the parsed AST:
 * ClassDescription has no method-level introspection, so there is no AST way
 * to see that a class declares render(): JsonResponse. IsA (already used in
 * NamingRules) resolves the same way, via is_a() with autoloading.
 *
 * A class passes by declaring render(): JsonResponse itself, inheriting it
 * from a module base exception (ReflectionMethod walks the real inheritance
 * chain the same way method_exists() does, but also reads the declared return
 * type), or by extending Symfony's HttpException, which Laravel's handler already
 * renders without a render() method. A render() that exists but returns
 * something other than JsonResponse (or declares no return type at all) does
 * not satisfy the contract; the whole point is that a decoupled frontend gets
 * JSON back, not whatever shape a stray render() happens to produce.
 */
final class IsSelfRenderingException implements Expression
{
    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description(
            "{$theClass->getName()} should extend its module base exception, implement render(): JsonResponse itself, or extend Symfony's HttpException",
            $because,
        );
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $fqcn = $theClass->getFQCN();

        if ($this->hasJsonResponseRenderMethod($fqcn) || is_a($fqcn, HttpExceptionInterface::class, true)) {
            return;
        }

        $violations->add(Violation::create(
            $fqcn,
            ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
            $theClass->getFilePath(),
        ));
    }

    private function hasJsonResponseRenderMethod(string $fqcn): bool
    {
        try {
            $method = new ReflectionMethod($fqcn, 'render');
        } catch (ReflectionException) {
            return false;
        }

        $returnType = $method->getReturnType();

        // Laravel's exception handler calls $e->render($request) from outside the class,
        // so a non-public render() can never actually be invoked there.
        return $method->isPublic()
            && $returnType instanceof ReflectionNamedType
            && $returnType->getName() === JsonResponse::class;
    }
}
