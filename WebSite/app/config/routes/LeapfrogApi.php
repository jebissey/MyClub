<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class LeapfrogApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $leapfrogApi = fn() => $this->apiFactory->makeLeapfrogApi();

        $this->routes[] = new Route('POST /api/leapfrog', $leapfrogApi, 'logMovement');

        return $this->routes;
    }
}
