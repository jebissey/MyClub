<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Exercise implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $ctrl = fn() => $this->controllerFactory->makeExerciseController();

        $this->routes[] = new Route('GET  /exercise',                 $ctrl, 'create');
        $this->routes[] = new Route('GET  /exercise/edit/@id:[0-9]+', $ctrl, 'edit');
        $this->routes[] = new Route('POST /exercise/save/@id:[0-9]+', $ctrl, 'save');
        $this->routes[] = new Route('GET  /exercise/play/@id:[0-9]+', $ctrl, 'play');

        return $this->routes;
    }
}
