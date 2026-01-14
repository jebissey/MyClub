<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Event implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $eventController = fn() => $this->controllerFactory->makeEventController();

        $this->routes[] = new Route('GET  /eventManager', $eventController, 'home');
        $this->routes[] = new Route('GET  /eventManager/help', $eventController, 'help');
        $this->routes[] = new Route('GET  /event/chat/@id:[0-9]+', $eventController, 'showEventChat');
        $this->routes[] = new Route('GET  /event/@id:[0-9]+', $eventController, 'show');
        $this->routes[] = new Route('GET  /event/@id:[0-9]+/register', $eventController, 'registerSet');
        $this->routes[] = new Route('GET  /event/@id:[0-9]+/unregister', $eventController, 'registerUnset');
        $this->routes[] = new Route('GET  /event/@id:[0-9]+/@token:[a-f0-9]+', $eventController, 'registerSet');
        $this->routes[] = new Route('GET  /events/crosstab', $eventController, 'showEventCrosstab');
        $this->routes[] = new Route('GET  /nextEvents', $eventController, 'nextEvents');
        $this->routes[] = new Route('GET  /weekEvents', $eventController, 'weekEvents');

        return $this->routes;
    }
}
