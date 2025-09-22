<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class ImportApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $importApi = fn() => $this->apiFactory->makeImportApi();

        $this->routes[] = new Route('POST /api/import/headers', $importApi, 'getHeadersFromCSV');

        return $this->routes;
    }
}
