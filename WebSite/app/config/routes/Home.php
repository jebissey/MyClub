<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Home implements RouteInterface
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
        $homeController = fn() => $this->controllerFactory->makeHomeController();

        $this->routes[] = new Route('GET  /', $homeController, 'home');
        $this->routes[] = new Route('GET  /help', $homeController, 'helpHome');
        $this->routes[] = new Route('GET  /legal/notice', $homeController, 'legalNotice');
        $this->routes[] = new Route('GET  /signpost', $homeController, 'signpost');

        return $this->routes;
    }
}
