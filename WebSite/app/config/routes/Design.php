<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Design implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $designController = fn() => $this->controllerFactory->makeDesignController();

        $this->routes[] = new Route('GET  /designs', $designController, 'index');
        $this->routes[] = new Route('GET  /design/create', $designController, 'create');
        $this->routes[] = new Route('POST /design/save', $designController, 'save');

        return $this->routes;
    }
}
