<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class EventNeedApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $eventNeedApi = fn() => $this->apiFactory->makeEventNeedApi();

        $this->routes[] = new Route('GET  /api/event/needs/@id:[0-9]+', $eventNeedApi, 'getEventNeeds');
        $this->routes[] = new Route('POST /api/need/delete/@id:[0-9]+', $eventNeedApi, 'deleteNeed');
        $this->routes[] = new Route('POST /api/need/save', $eventNeedApi, 'saveNeed');

        return $this->routes;
    }
}
