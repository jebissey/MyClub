<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Designer implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $designerController = fn() => $this->controllerFactory->makeDesignerController();

        $this->routes[] = new Route('GET  /designer', $designerController, 'homeDesigner');
        $this->routes[] = new Route('GET  /designer/help', $designerController, 'helpDesigner');

        return $this->routes;
    }
}
