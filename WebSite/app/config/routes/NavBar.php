<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class NavBar implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $navBarController = fn() => $this->controllerFactory->makeNavBarController();

        $this->routes[] = new Route('GET /navbar', $navBarController, 'index');
        $this->routes[] = new Route('GET /navbar/show/article/@id:[0-9]+', $navBarController, 'showArticle');
        $this->routes[] = new Route('GET /navbar/show/arwards', $navBarController, 'showArwards');

        return $this->routes;
    }
}
