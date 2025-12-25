<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Kanban implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $kanbanController = fn() => $this->controllerFactory->makeKanbanController();

        $this->routes[] = new Route('GET  /kanban', $kanbanController, 'board');

        return $this->routes;
    }
}
