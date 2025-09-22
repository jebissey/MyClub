<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class EventNeedTypeApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $eventNeedTypeApi = fn() => $this->apiFactory->makeEventNeedTypeApi();

        $this->routes[] = new Route('POST /api/need/type/delete/@id:[0-9]+', $eventNeedTypeApi, 'deleteNeedType');
        $this->routes[] = new Route('POST /api/need/type/save', $eventNeedTypeApi, 'saveNeedType');
        $this->routes[] = new Route('GET  /api/needs-by-need-type/@id:[0-9]+', $eventNeedTypeApi, 'getNeedsByNeedType');

        return $this->routes;
    }
}
