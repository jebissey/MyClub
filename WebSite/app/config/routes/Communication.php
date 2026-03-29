<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Communication implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $communicationController = fn() => $this->controllerFactory->makeCommunicationController();

        $this->routes[] = new Route('GET  /communication', $communicationController, 'edit');

        return $this->routes;
    }
}