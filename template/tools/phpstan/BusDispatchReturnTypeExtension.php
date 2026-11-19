<?php

declare(strict_types=1);

namespace Tools\PHPStan;

use Illuminate\Contracts\Bus\Dispatcher;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function in_array;

/**
 * Teaches PHPStan that `Dispatcher::dispatch($command)` returns the type that
 * the command/query's handler declares from `handle()`.
 *
 * Illuminate\Contracts\Bus\Dispatcher::dispatch() is declared `: mixed`, so a
 * typed Result DTO loses all its type information the moment it crosses the
 * bus. Controllers and handlers dispatch a command and read `$result->prop`,
 * which then reports access-on-mixed everywhere downstream.
 *
 * Resolution is convention-based: the project's naming standard guarantees
 * `{Action}Command` -> `{Action}CommandHandler` and `{Action}Query` ->
 * `{Action}QueryHandler` (registered via Bus::map()), so the handler class is
 * the dispatched class name with a `Handler` suffix. When no such handler
 * exists (e.g. a queued Job), this returns null and the default `mixed` stands.
 */
final class BusDispatchReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    public function getClass(): string
    {
        return Dispatcher::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['dispatch', 'dispatchSync', 'dispatchNow'], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        $args = $methodCall->getArgs();

        if ($args === []) {
            return null;
        }

        $returnTypes = [];

        foreach ($scope->getType($args[0]->value)->getObjectClassNames() as $dispatchedClass) {
            $handlerClass = $dispatchedClass.'Handler';

            if (! $this->reflectionProvider->hasClass($handlerClass)) {
                continue;
            }

            $handlerType = new ObjectType($handlerClass);

            if (! $handlerType->hasMethod('handle')->yes()) {
                continue;
            }

            $variants = $handlerType->getMethod('handle', $scope)->getVariants();

            $returnTypes[] = $variants[0]->getReturnType();
        }

        return $returnTypes === [] ? null : TypeCombinator::union(...$returnTypes);
    }
}
