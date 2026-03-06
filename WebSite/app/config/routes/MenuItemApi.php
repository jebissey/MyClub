<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class MenuItemApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $menuItemApi = fn() => $this->apiFactory->makeMenuItemApi();

        $this->routes[] = new Route('POST /api/menuItem/delete/@id:[0-9]+', $menuItemApi, 'deleteMenuItem');
        $this->routes[] = new Route('GET  /api/menuItem/get/@id:[0-9]+', $menuItemApi, 'getMenuItem');
        $this->routes[] = new Route('POST /api/menuItem/save', $menuItemApi, 'saveMenuItem');
        $this->routes[] = new Route('POST /api/menuItem/updatePositions', $menuItemApi, 'updateMenuPositions');

        return $this->routes;
    }
}
