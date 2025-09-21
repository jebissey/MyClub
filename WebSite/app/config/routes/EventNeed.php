<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class EventNeed implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $eventNeedController = fn() => $this->controllerFactory->makeEventNeedController();

        $this->routes[] = new Route('GET /needs', $eventNeedController, 'needs');

        return $this->routes;
    }
}
