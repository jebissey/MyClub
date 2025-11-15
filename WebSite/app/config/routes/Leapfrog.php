<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Leapfrog implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $leapfrogController = fn() => $this->controllerFactory->makeLeapfrogController();

        $this->routes[] = new Route('GET /game/leapfrog', $leapfrogController, 'play');
        $this->routes[] = new Route('GET /game/leapfrog/statistics', $leapfrogController, 'statistics');

        return $this->routes;
    }
}