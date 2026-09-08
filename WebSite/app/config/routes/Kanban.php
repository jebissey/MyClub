<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

class Kanban implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $kanbanController = fn() => $this->controllerFactory->makeKanbanController();

        $this->routes[] = new Route('GET  /kanban', $kanbanController, 'board');
        $this->routes[] = new Route('GET  /kanban/help', $kanbanController, 'help');

        return $this->routes;
    }
}
