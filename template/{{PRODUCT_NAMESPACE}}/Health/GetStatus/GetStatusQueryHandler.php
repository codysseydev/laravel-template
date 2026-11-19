<?php

declare(strict_types=1);

namespace {{PRODUCT_NAMESPACE}}\Health\GetStatus;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

final readonly class GetStatusQueryHandler
{
    public function __construct(
        private ConnectionInterface $database,
        private ConfigRepository $config,
    ) {}

    public function handle(GetStatusQuery $query): StatusReport
    {
        $app = $this->config->get('app.name');
        $environment = $this->config->get('app.env');

        return new StatusReport(
            is_string($app) ? $app : '',
            is_string($environment) ? $environment : '',
            $this->databaseIsReachable(),
        );
    }

    private function databaseIsReachable(): bool
    {
        try {
            $this->database->selectOne('select 1');
        } catch (QueryException) {
            return false;
        }

        return true;
    }
}
