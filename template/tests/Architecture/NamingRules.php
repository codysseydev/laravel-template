<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsA;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Naming suffixes, anchored on the framework base class a type extends. A
 * controller, its request and its resource sit in the same slice folder, so a
 * namespace cannot tell them apart.
 */
final class NamingRules
{
    /**
     * @return list<ArchRule>
     */
    public static function rules(): array
    {
        return [
            Rule::allClasses()
                ->that(new IsA(FormRequest::class))
                ->should(new HaveNameMatching('*Request'))
                ->because('form requests must carry the Request suffix'),

            Rule::allClasses()
                ->that(new IsA(JsonResource::class))
                ->should(new HaveNameMatching('*Resource'))
                ->because('API resources must carry the Resource suffix'),
        ];
    }
}
