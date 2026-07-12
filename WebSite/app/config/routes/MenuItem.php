<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class MenuItem implements RouteInterface
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
        $menuItemController = fn() => $this->controllerFactory->makeMenuItemController();

        $this->routes[] = new Route('GET /menu', $menuItemController, 'index');
        $this->routes[] = new Route('GET /menu/show/article/@id:[0-9]+', $menuItemController, 'showArticle');
        $this->routes[] = new Route('GET /menu/show/arwards', $menuItemController, 'showArwards');

        return $this->routes;
    }
}
