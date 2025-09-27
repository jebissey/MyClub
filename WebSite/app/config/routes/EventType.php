<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class EventType implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $eventTypeController = fn() => $this->controllerFactory->makeEventTypeController();

        $this->routes[] = new Route('GET  /eventTypes', $eventTypeController, 'index');
        $this->routes[] = new Route('GET  /eventType/create', $eventTypeController, 'create');
        $this->routes[] = new Route('GET  /eventType/edit/@id:[0-9]+', $eventTypeController, 'edit');
        $this->routes[] = new Route('POST /eventType/edit/@id:[0-9]+', $eventTypeController, 'update');
        $this->routes[] = new Route('GET  /eventType/delete/@id:[0-9]+', $eventTypeController, 'delete');

        return $this->routes;
    }
}
