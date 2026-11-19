<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Health\GetStatus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StatusResource extends JsonResource
{
    public function __construct(private readonly StatusReport $report)
    {
        parent::__construct($report);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'app' => $this->report->app,
            'environment' => $this->report->environment,
            'database_reachable' => $this->report->databaseReachable,
        ];
    }
}
