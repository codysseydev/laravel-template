<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\IsNotA;
use Arkitect\Expression\ForClasses\IsReadonly;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\Rule;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Application-layer classes are `final`. Handlers, commands, queries and
 * domain events are also `readonly`.
 *
 * Function calls and file directives (`now()`, `app()`, `declare(strict_types=1)`)
 * are invisible to PHPArkitect. Rector, PHPStan and Pint cover those.
 */
final class ClassShapeRules
{
    /**
     * @return list<ArchRule>
     */
    public static function rules(): array
    {
        $namespace = ProductModules::namespace();

        return [
            Rule::allClasses()
                ->that(new HaveNameMatching('*Controller'))
                ->should(new IsFinal)
                ->because('controllers are single-action and must be final'),

            Rule::allClasses()
                ->that(new HaveNameMatching('*Handler'))
                ->should(new IsFinal)
                ->because('command and query handlers must be final'),

            Rule::allClasses()
                ->that(new HaveNameMatching('*Handler'))
                ->should(new IsReadonly)
                ->because('command and query handlers must be readonly'),

            // Console commands extend Illuminate\Console\Command and are mutable,
            // so only domain commands are held to the readonly contract.
            Rule::allClasses()
                ->that(new HaveNameMatching('*Command'))
                ->andThat(new IsNotA(Command::class))
                ->should(new IsFinal)
                ->because('domain commands must be final'),

            Rule::allClasses()
                ->that(new HaveNameMatching('*Command'))
                ->andThat(new IsNotA(Command::class))
                ->should(new IsReadonly)
                ->because('domain commands are immutable DTOs and must be readonly'),

            Rule::allClasses()
                ->that(new HaveNameMatching('*Query'))
                ->should(new IsFinal)
                ->because('queries must be final'),

            Rule::allClasses()
                ->that(new HaveNameMatching('*Query'))
                ->should(new IsReadonly)
                ->because('queries are immutable DTOs and must be readonly'),

            Rule::allClasses()
                ->that(new HaveNameMatching('*Event'))
                ->andThat(new ResideInOneOfTheseNamespaces($namespace))
                ->should(new IsFinal)
                ->because('domain events must be final'),

            // `*Event` also matches Eloquent models that record a received event.
            // A model carries mutable state, so only domain events must be readonly.
            Rule::allClasses()
                ->that(new HaveNameMatching('*Event'))
                ->andThat(new ResideInOneOfTheseNamespaces($namespace))
                ->andThat(new IsNotA(Model::class))
                ->should(new IsReadonly)
                ->because('domain events are immutable data DTOs and must be readonly'),
        ];
    }
}
