<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class NavbarApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $navbarApi = fn() => $this->apiFactory->makeNavbarApi();

        $this->routes[] = new Route('POST /api/navbar/deleteItem/@id:[0-9]+', $navbarApi, 'deleteNavbarItem');
        $this->routes[] = new Route('GET  /api/navbar/getItem/@id:[0-9]+', $navbarApi, 'getNavbarItem');
        $this->routes[] = new Route('POST /api/navbar/saveItem', $navbarApi, 'saveNavbarItem');
        $this->routes[] = new Route('POST /api/navbar/updatePositions', $navbarApi, 'updateNavbarPositions');

        return $this->routes;
    }
}
