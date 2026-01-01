<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class EventAttributeApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $eventAttributeApi = fn() => $this->apiFactory->makeEventAttributeApi();

        $this->routes[] = new Route('POST /api/attribute/create', $eventAttributeApi, 'createAttribute');
        $this->routes[] = new Route('POST /api/attribute/delete/@id:[0-9]+', $eventAttributeApi, 'deleteAttribute');
        $this->routes[] = new Route('GET  /api/attributes/eventType/@id:[0-9]+', $eventAttributeApi, 'getAttributesByEventType');
        $this->routes[] = new Route('GET  /api/attributes/list', $eventAttributeApi, 'getAttributes');
        $this->routes[] = new Route('POST /api/attribute/update', $eventAttributeApi, 'updateAttribute');

        return $this->routes;
    }
}
