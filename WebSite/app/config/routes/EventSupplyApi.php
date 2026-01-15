<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class EventSupplyApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $eventSupplyApi = fn() => $this->apiFactory->makeEventSupplyApi();

        $this->routes[] = new Route('POST /api/event/updateSupply', $eventSupplyApi, 'updateSupply');
        $this->routes[] = new Route('GET  /api/participants/supplies', $eventSupplyApi, 'participantsSupplies');

        return $this->routes;
    }
}
