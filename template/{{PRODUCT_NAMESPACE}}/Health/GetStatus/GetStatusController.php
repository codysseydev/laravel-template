<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Health\GetStatus;

use Illuminate\Contracts\Bus\Dispatcher;

final readonly class GetStatusController
{
    public function __construct(private Dispatcher $dispatcher) {}

    public function __invoke(): StatusResource
    {
        $report = $this->dispatcher->dispatch(new GetStatusQuery);

        return new StatusResource($report);
    }
}
